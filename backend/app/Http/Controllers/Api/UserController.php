<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * 获取用户列表（支持搜索、筛选、分页）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::with('company');

            // 搜索功能（按姓名或账户名搜索）
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('account', 'like', "%{$search}%");
                });
            }

            // 角色筛选
            if ($request->filled('role')) {
                $query->where('role', $request->input('role'));
            }

            // 创建时间范围筛选
            if ($request->filled('created_from')) {
                $query->whereDate('created_at', '>=', $request->input('created_from'));
            }
            if ($request->filled('created_to')) {
                $query->whereDate('created_at', '<=', $request->input('created_to'));
            }

            // 排序
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // 分页
            $perPage = $request->input('per_page', 15);
            $users = $query->paginate($perPage);

            return ApiResponse::success([
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ], '获取用户列表成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取用户列表失败', $e->getMessage());
        }
    }

    /**
     * 创建新用户
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'account' => 'required|string|max:100|unique:users',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,user',
                'company_id' => 'nullable|integer|exists:companies,id',
            ], [
                'name.required' => '姓名不能为空',
                'name.max' => '姓名不能超过255个字符',
                'account.required' => '账户名不能为空',
                'account.max' => '账户名不能超过100个字符',
                'account.unique' => '账户名已存在',
                'password.required' => '密码不能为空',
                'password.min' => '密码至少6位',
                'role.required' => '角色不能为空',
                'role.in' => '角色必须是admin或user',
                'company_id.integer' => '公司ID必须是整数',
                'company_id.exists' => '所选公司不存在',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $user = User::create([
                'name' => $request->name,
                'account' => $request->account,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'company_id' => $request->company_id,
            ]);

            return ApiResponse::created($user, '用户创建成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('用户创建失败', $e->getMessage());
        }
    }

    /**
     * 获取指定用户信息
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = User::with('company')->find($id);

            if (!$user) {
                return ApiResponse::notFound('用户不存在');
            }

            return ApiResponse::success($user, '获取用户信息成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取用户信息失败', $e->getMessage());
        }
    }

    /**
     * 更新用户信息
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponse::notFound('用户不存在');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'account' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('users')->ignore($user->id),
                ],
                'password' => 'sometimes|string|min:6',
                'role' => 'sometimes|required|in:admin,user',
                'company_id' => 'sometimes|nullable|integer|exists:companies,id',
            ], [
                'name.required' => '姓名不能为空',
                'name.max' => '姓名不能超过255个字符',
                'account.required' => '账户名不能为空',
                'account.max' => '账户名不能超过100个字符',
                'account.unique' => '账户名已存在',
                'password.min' => '密码至少6位',
                'role.required' => '角色不能为空',
                'role.in' => '角色必须是admin或user',
                'company_id.integer' => '公司ID必须是整数',
                'company_id.exists' => '所选公司不存在',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            // 更新用户信息
            if ($request->filled('name')) {
                $user->name = $request->name;
            }
            if ($request->filled('account')) {
                $user->account = $request->account;
            }
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            if ($request->filled('role')) {
                $user->role = $request->role;
            }
            if ($request->has('company_id')) {
                $user->company_id = $request->company_id;
            }

            $user->save();

            return ApiResponse::success($user, '用户更新成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('用户更新失败', $e->getMessage());
        }
    }

    /**
     * 删除用户
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponse::notFound('用户不存在');
            }

            // 防止删除当前登录用户
            if (auth()->id() === $user->id) {
                return ApiResponse::forbidden('不能删除当前登录用户');
            }

            $user->delete();

            return ApiResponse::success(null, '用户删除成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('用户删除失败', $e->getMessage());
        }
    }

    /**
     * 批量删除用户
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function batchDestroy(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array|min:1',
                'ids.*' => 'integer|exists:users,id',
            ], [
                'ids.required' => '用户ID不能为空',
                'ids.array' => '用户ID必须是数组',
                'ids.min' => '至少选择一个用户',
                'ids.*.integer' => '用户ID必须是整数',
                'ids.*.exists' => '用户不存在',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $ids = $request->input('ids');
            $currentUserId = auth()->id();

            // 防止删除当前登录用户
            if (in_array($currentUserId, $ids)) {
                return ApiResponse::forbidden('不能删除当前登录用户');
            }

            $deletedCount = User::whereIn('id', $ids)->delete();

            return ApiResponse::success([
                'deleted_count' => $deletedCount,
            ], "成功删除 {$deletedCount} 个用户");

        } catch (\Exception $e) {
            return ApiResponse::serverError('批量删除用户失败', $e->getMessage());
        }
    }

    /**
     * 重置用户密码
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function resetPassword(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponse::notFound('用户不存在');
            }

            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6',
            ], [
                'password.required' => '新密码不能为空',
                'password.min' => '密码至少6位',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return ApiResponse::success(null, '密码重置成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('密码重置失败', $e->getMessage());
        }
    }

    /**
     * 切换用户角色
     *
     * @param int $id
     * @return JsonResponse
     */
    public function toggleRole(int $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponse::notFound('用户不存在');
            }

            // 防止修改当前登录用户的角色
            if (auth()->id() === $user->id) {
                return ApiResponse::forbidden('不能修改当前登录用户的角色');
            }

            // 切换角色
            $user->role = $user->role === 'admin' ? 'user' : 'admin';
            $user->save();

            return ApiResponse::success($user, '用户角色切换成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('用户角色切换失败', $e->getMessage());
        }
    }

    /**
     * 获取用户统计信息
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $totalUsers = User::count();
            $adminUsers = User::where('role', 'admin')->count();
            $normalUsers = User::where('role', 'user')->count();
            $todayUsers = User::whereDate('created_at', today())->count();
            $thisWeekUsers = User::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count();
            $thisMonthUsers = User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            return ApiResponse::success([
                'total_users' => $totalUsers,
                'admin_users' => $adminUsers,
                'normal_users' => $normalUsers,
                'today_users' => $todayUsers,
                'this_week_users' => $thisWeekUsers,
                'this_month_users' => $thisMonthUsers,
            ], '获取用户统计信息成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取用户统计信息失败', $e->getMessage());
        }
    }
}