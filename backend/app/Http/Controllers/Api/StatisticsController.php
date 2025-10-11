<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\DataRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * 统计数据控制器
 * 提供数据领取统计和收集统计功能
 */
class StatisticsController extends Controller
{
    /**
     * 获取领取统计数据
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function claimStatistics(Request $request): JsonResponse
    {
        try {
            // 获取日期参数
            $date = $request->get('date');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // 构建查询条件
            $query = DataRecord::query();
            
            if ($date) {
                // 单日查询
                $query->whereDate('created_at', $date);
            } elseif ($startDate && $endDate) {
                // 日期范围查询
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } else {
                // 默认查询当天
                $query->whereDate('created_at', Carbon::today());
            }

            // 总体统计
            $totalRecords = $query->count();
            $claimedRecords = (clone $query)->where('is_claimed', true)->count();
            $completedRecords = (clone $query)->where('is_completed', true)->count();
            $completionRate = $claimedRecords > 0 ? round(($completedRecords / $claimedRecords) * 100, 2) : 0;

            // 用户领取统计
            $userStatistics = DB::table('data_records')
                ->join('users', 'data_records.claimer_id', '=', 'users.id')
                ->select([
                    'users.id as user_id',
                    'users.name as user_name',
                    DB::raw('COUNT(data_records.id) as claimed_count'),
                    DB::raw('SUM(CASE WHEN data_records.is_completed = 1 THEN 1 ELSE 0 END) as completed_count')
                ])
                ->where('data_records.is_claimed', true)
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
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function collectionStatistics(Request $request): JsonResponse
    {
        try {
            // 获取日期参数
            $date = $request->get('date');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // 构建查询条件
            $query = DataRecord::query();
            
            if ($date) {
                // 单日查询
                $query->whereDate('created_at', $date);
            } elseif ($startDate && $endDate) {
                // 日期范围查询
                $query->whereBetween('created_at', [
                    Carbon::parse($startDate)->startOfDay(),
                    Carbon::parse($endDate)->endOfDay()
                ]);
            } else {
                // 默认查询当天
                $query->whereDate('created_at', Carbon::today());
            }

            // 总体统计
            $totalRecords = $query->count();
            $claimedRecords = (clone $query)->where('is_claimed', true)->count();
            $completedRecords = (clone $query)->where('is_completed', true)->count();
            $duplicateRecords = (clone $query)->where('is_duplicate', true)->count();
            
            $completionRate = $totalRecords > 0 ? round(($completedRecords / $totalRecords) * 100, 2) : 0;
            $duplicateRate = $totalRecords > 0 ? round(($duplicateRecords / $totalRecords) * 100, 2) : 0;

            // 用户提交统计
            $userStatistics = DB::table('data_records')
                ->join('users', 'data_records.submitter_id', '=', 'users.id')
                ->select([
                    'users.id as user_id',
                    'users.name as user_name',
                    DB::raw('COUNT(data_records.id) as submitted_count'),
                    DB::raw('SUM(CASE WHEN data_records.is_duplicate = 1 THEN 1 ELSE 0 END) as duplicate_count')
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
                    $item->duplicate_rate = $item->submitted_count > 0 
                        ? round(($item->duplicate_count / $item->submitted_count) * 100, 2) 
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