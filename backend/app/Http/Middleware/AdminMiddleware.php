<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\ApiResponse;

class AdminMiddleware
{
    /**
     * 处理传入的请求
     * 检查用户是否为管理员
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 检查用户是否已认证
        if (!auth()->check()) {
            return ApiResponse::unauthorized('请先登录');
        }

        // 检查用户是否为管理员
        if (!auth()->user()->isAdmin()) {
            return ApiResponse::forbidden('权限不足，仅管理员可访问');
        }

        return $next($request);
    }
}