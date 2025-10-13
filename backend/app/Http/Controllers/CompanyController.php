<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

/**
 * 公司管理控制器
 * 处理公司的增删改查操作
 */
class CompanyController extends Controller
{
    /**
     * 获取公司列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Company::query();

        // 搜索过滤
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // 状态过滤
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // 分页
        $perPage = $request->get('per_page', 15);
        $companies = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    /**
     * 创建新公司
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code',
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $company = Company::create($validated);

        return response()->json([
            'success' => true,
            'message' => '公司创建成功',
            'data' => $company,
        ], 201);
    }

    /**
     * 获取指定公司详情
     */
    public function show(Company $company): JsonResponse
    {
        $company->load(['users', 'dataRecordAssignments']);

        return response()->json([
            'success' => true,
            'data' => $company,
        ]);
    }

    /**
     * 更新公司信息
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('companies', 'code')->ignore($company->id),
            ],
            'description' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $company->update($validated);

        return response()->json([
            'success' => true,
            'message' => '公司信息更新成功',
            'data' => $company,
        ]);
    }

    /**
     * 删除公司
     */
    public function destroy(Company $company): JsonResponse
    {
        // 检查是否有关联的用户或数据分发
        if ($company->users()->exists() || $company->dataRecordAssignments()->exists()) {
            return response()->json([
                'success' => false,
                'message' => '该公司下还有用户或数据分发记录，无法删除',
            ], 422);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => '公司删除成功',
        ]);
    }

    /**
     * 获取启用的公司列表（用于下拉选择）
     */
    public function active(): JsonResponse
    {
        $companies = Company::active()
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $companies,
        ]);
    }

    /**
     * 切换公司状态
     */
    public function toggleStatus(Company $company): JsonResponse
    {
        $company->update(['is_active' => !$company->is_active]);

        return response()->json([
            'success' => true,
            'message' => $company->is_active ? '公司已启用' : '公司已禁用',
            'data' => $company,
        ]);
    }
}
