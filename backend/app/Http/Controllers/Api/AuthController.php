<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * 用户认证控制器
 * 处理用户登录、注册、登出等认证相关功能
 */
class AuthController extends Controller
{
    /**
     * 用户注册
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'account' => 'required|string|max:100|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ], [
                'name.required' => '姓名不能为空',
                'account.required' => '账户名不能为空',
                'account.unique' => '账户名已被注册',
                'password.required' => '密码不能为空',
                'password.min' => '密码至少6位',
                'password.confirmed' => '两次密码不一致',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $user = User::create([
                'name' => $request->name,
                'account' => $request->account,
                'password' => Hash::make($request->password),
                'role' => 'user', // 默认为普通用户
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return ApiResponse::created([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'account' => $user->account,
                    'role' => $user->role,
                ],
                'token' => $token,
            ], '注册成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('注册失败', $e->getMessage());
        }
    }

    /**
     * 用户登录
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'account' => 'required|string',
                'password' => 'required',
            ], [
                'account.required' => '账户名不能为空',
                'password.required' => '密码不能为空',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            if (!Auth::attempt($request->only('account', 'password'))) {
                return ApiResponse::unauthorized('账户名或密码错误');
            }

            $user = Auth::user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return ApiResponse::success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'account' => $user->account,
                    'role' => $user->role,
                ],
                'token' => $token,
            ], '登录成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('登录失败', $e->getMessage());
        }
    }

    /**
     * 用户登出
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return ApiResponse::success(null, '登出成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('登出失败', $e->getMessage());
        }
    }

    /**
     * 获取当前用户信息
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            return ApiResponse::success([
                'id' => $user->id,
                'name' => $user->name,
                'account' => $user->account,
                'role' => $user->role,
            ], '获取用户信息成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('获取用户信息失败', $e->getMessage());
        }
    }

    /**
     * 更改密码
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ], [
                'current_password.required' => '当前密码不能为空',
                'new_password.required' => '新密码不能为空',
                'new_password.min' => '新密码至少6位',
                'new_password.confirmed' => '两次新密码不一致',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $user = $request->user();

            // 验证当前密码是否正确
            if (!Hash::check($request->current_password, $user->password)) {
                return ApiResponse::error('当前密码错误');
            }

            // 检查新密码是否与当前密码相同
            if (Hash::check($request->new_password, $user->password)) {
                return ApiResponse::error('新密码不能与当前密码相同');
            }

            // 更新密码
            $user->password = Hash::make($request->new_password);
            $user->save();

            return ApiResponse::success(null, '密码修改成功');

        } catch (\Exception $e) {
            return ApiResponse::serverError('密码修改失败', $e->getMessage());
        }
    }
}
