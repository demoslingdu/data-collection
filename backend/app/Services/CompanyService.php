<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 公司服务类
 * 处理公司相关的业务逻辑
 */
class CompanyService
{
    /**
     * 获取公司列表（带分页和过滤）
     */
    public function getCompanies(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Company::query();

        // 搜索过滤
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        // 状态过滤
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * 获取启用的公司列表
     */
    public function getActiveCompanies(): Collection
    {
        return Company::active()
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();
    }

    /**
     * 创建公司
     */
    public function createCompany(array $data): Company
    {
        return Company::create($data);
    }

    /**
     * 更新公司信息
     */
    public function updateCompany(Company $company, array $data): Company
    {
        $company->update($data);
        return $company->fresh();
    }

    /**
     * 删除公司
     */
    public function deleteCompany(Company $company): bool
    {
        // 检查是否有关联的用户或数据分发
        if ($company->users()->exists() || $company->dataRecordAssignments()->exists()) {
            throw new \Exception('该公司下还有用户或数据分发记录，无法删除');
        }

        return $company->delete();
    }

    /**
     * 切换公司状态
     */
    public function toggleCompanyStatus(Company $company): Company
    {
        $company->update(['is_active' => !$company->is_active]);
        return $company->fresh();
    }

    /**
     * 根据代码查找公司
     */
    public function findByCode(string $code): ?Company
    {
        return Company::byCode($code)->first();
    }

    /**
     * 获取公司统计信息
     */
    public function getCompanyStatistics(Company $company): array
    {
        return [
            'users_count' => $company->users()->count(),
            'assignments_count' => $company->dataRecordAssignments()->count(),
            'pending_assignments' => $company->dataRecordAssignments()->pending()->count(),
            'in_progress_assignments' => $company->dataRecordAssignments()->inProgress()->count(),
            'completed_assignments' => $company->dataRecordAssignments()->completed()->count(),
        ];
    }

    /**
     * 验证公司代码唯一性
     */
    public function isCodeUnique(string $code, ?int $excludeId = null): bool
    {
        $query = Company::where('code', $code);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }
}