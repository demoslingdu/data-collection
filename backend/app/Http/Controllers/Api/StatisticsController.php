<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\DataRecord;
use App\Models\DataRecordAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * 统计数据控制器
 * 提供数据领取统计和收集统计功能
 */
class StatisticsController extends Controller
{
    /**
     * 获取领取统计数据
     * 基于新的数据分发系统（data_record_assignments 表）进行统计
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function claimStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 获取公司ID参数，如果未提供则使用当前用户的公司
            $companyId = $request->get('company_id', $user->company_id);
            
            // 权限检查：普通用户只能查看自己公司的数据
            if ($user->role !== 'admin' && $companyId != $user->company_id) {
                return ApiResponse::error('无权限查看其他公司的统计数据', 403);
            }

            // 获取日期参数
            $date = $request->get('date');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // 构建基于 data_record_assignments 表的查询条件
            $query = DataRecordAssignment::where('company_id', $companyId);
            
            if ($date) {
                // 单日查询
                $query->whereDate('assigned_at', $date);
            } elseif ($startDate && $endDate) {
                // 日期范围查询
                $query->whereBetween('assigned_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } else {
                // 默认查询当天
                $query->whereDate('assigned_at', Carbon::today());
            }

            // 总体统计 - 基于分发记录
            $totalRecords = $query->count(); // 分发给当前公司的总数据量
            $claimedRecords = (clone $query)->whereNotNull('assigned_to')->count(); // 已领取数据（assigned_to 不为 null）
            $completedRecords = (clone $query)->where('is_completed', true)->count(); // 已完成数据
            $completionRate = $claimedRecords > 0 ? round(($completedRecords / $claimedRecords) * 100, 2) : 0;

            // 用户领取统计 - 基于分发记录
            $userStatistics = DB::table('data_record_assignments')
                ->join('users', 'data_record_assignments.assigned_to', '=', 'users.id')
                ->select([
                    'users.id as user_id',
                    'users.name as user_name',
                    DB::raw('COUNT(data_record_assignments.id) as claimed_count'),
                    DB::raw('SUM(CASE WHEN data_record_assignments.is_completed = 1 THEN 1 ELSE 0 END) as completed_count')
                ])
                ->where('data_record_assignments.company_id', $companyId)
                ->whereNotNull('data_record_assignments.assigned_to') // 只统计已领取的记录
                ->when($date, function ($query, $date) {
                    return $query->whereDate('data_record_assignments.assigned_at', $date);
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('data_record_assignments.assigned_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                })
                ->when(!$date && !($startDate && $endDate), function ($query) {
                    return $query->whereDate('data_record_assignments.assigned_at', Carbon::today());
                })
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('claimed_count')
                ->get()
                ->map(function ($item) {
                    $item->completion_rate = $item->claimed_count > 0 
                        ? round(($item->completed_count / $item->claimed_count) * 100, 2) 
                        : 0;
                    return $item;
                });

            $result = [
                'total_records' => $totalRecords,
                'claimed_records' => $claimedRecords,
                'completed_records' => $completedRecords,
                'completion_rate' => $completionRate,
                'user_statistics' => $userStatistics
            ];

            return ApiResponse::success($result, '获取领取统计数据成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取领取统计数据失败', $e->getMessage());
        }
    }

    /**
     * 获取收集统计数据
     * 结合新的数据分发系统和原有数据记录表进行统计
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function collectionStatistics(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // 获取公司ID参数，如果未提供则使用当前用户的公司
            $companyId = $request->get('company_id', $user->company_id);
            
            // 权限检查：普通用户只能查看自己公司的数据
            if ($user->role !== 'admin' && $companyId != $user->company_id) {
                return ApiResponse::error('无权限查看其他公司的统计数据', 403);
            }

            // 获取日期参数
            $date = $request->get('date');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // 构建基于 data_records 表的查询条件（用于总数据量和重复数据统计）
            $recordQuery = DataRecord::query();
            
            if ($date) {
                // 单日查询
                $recordQuery->whereDate('created_at', $date);
            } elseif ($startDate && $endDate) {
                // 日期范围查询
                $recordQuery->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } else {
                // 默认查询当天
                $recordQuery->whereDate('created_at', Carbon::today());
            }

            // 构建基于 data_record_assignments 表的查询条件（用于领取和完成统计）
            $assignmentQuery = DataRecordAssignment::where('company_id', $companyId);
            
            if ($date) {
                $assignmentQuery->whereDate('assigned_at', $date);
            } elseif ($startDate && $endDate) {
                $assignmentQuery->whereBetween('assigned_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } else {
                $assignmentQuery->whereDate('assigned_at', Carbon::today());
            }

            // 总体统计
            $totalRecords = $recordQuery->count(); // 基于 data_records 表统计总数据量
            $claimedRecords = $assignmentQuery->count(); // 基于 data_record_assignments 表统计已分发的数据
            $completedRecords = (clone $assignmentQuery)->where('is_completed', true)->count(); // 基于 data_record_assignments 表统计已完成的数据
            $duplicateRecords = (clone $recordQuery)->where('is_duplicate', true)->count(); // 基于 data_records 表统计重复数据
            
            $completionRate = $claimedRecords > 0 ? round(($completedRecords / $claimedRecords) * 100, 2) : 0;
            $duplicateRate = $totalRecords > 0 ? round(($duplicateRecords / $totalRecords) * 100, 2) : 0;

            // 用户提交统计 - 基于 data_records 表，同时关联 data_record_assignments 表获取已通过数据
            $userStatistics = DB::table('data_records')
                ->join('users', 'data_records.submitter_id', '=', 'users.id')
                ->leftJoin('data_record_assignments', function ($join) use ($companyId) {
                    $join->on('data_records.id', '=', 'data_record_assignments.data_record_id')
                         ->where('data_record_assignments.company_id', '=', $companyId)
                         ->whereNotNull('data_record_assignments.assigned_to');
                })
                ->select([
                    'users.id as user_id',
                    'users.name as user_name',
                    DB::raw('COUNT(DISTINCT data_records.id) as submitted_count'),
                    DB::raw('SUM(CASE WHEN data_records.is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate_count'),
                    DB::raw('COUNT(DISTINCT CASE WHEN data_record_assignments.assigned_to = users.id THEN data_record_assignments.id END) as claimed_count'),
                    DB::raw('COUNT(DISTINCT CASE WHEN data_record_assignments.assigned_to = users.id AND data_record_assignments.is_completed = 1 THEN data_record_assignments.id END) as completed_count')
                ])
                ->when($date, function ($query, $date) {
                    return $query->whereDate('data_records.created_at', $date);
                })
                ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                    return $query->whereBetween('data_records.created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                })
                ->when(!$date && !($startDate && $endDate), function ($query) {
                    return $query->whereDate('data_records.created_at', Carbon::today());
                })
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('submitted_count')
                ->get()
                ->map(function ($item) {
                    // 计算重复率
                    $item->duplicate_rate = $item->submitted_count > 0 
                        ? round(($item->duplicate_count / $item->submitted_count) * 100, 2) 
                        : 0;
                    
                    // 计算通过率（基于已领取的数据）
                    $item->completion_rate = $item->claimed_count > 0 
                        ? round(($item->completed_count / $item->claimed_count) * 100, 2) 
                        : 0;
                    
                    return $item;
                });

            $result = [
                'total_records' => $totalRecords,
                'claimed_records' => $claimedRecords,
                'completed_records' => $completedRecords,
                'completion_rate' => $completionRate,
                'duplicate_records' => $duplicateRecords,
                'duplicate_rate' => $duplicateRate,
                'user_statistics' => $userStatistics
            ];

            return ApiResponse::success($result, '获取收集统计数据成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取收集统计数据失败', $e->getMessage());
        }
    }
}