<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataRecordController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ImageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// 公开路由（无需认证）
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// 需要认证的路由
Route::middleware('auth:sanctum')->group(function () {
    // 用户认证相关
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });

    // 数据记录相关
    Route::prefix('data-records')->group(function () {
        Route::get('/', [DataRecordController::class, 'index']);           // 获取列表
        Route::post('/', [DataRecordController::class, 'store']);          // 创建记录
        Route::get('statistics', [DataRecordController::class, 'statistics']); // 获取统计信息
        Route::get('{id}', [DataRecordController::class, 'show']);         // 获取详情
        Route::put('{id}', [DataRecordController::class, 'update']);       // 更新记录
        Route::delete('{id}', [DataRecordController::class, 'destroy']);   // 删除记录
        Route::delete('batch', [DataRecordController::class, 'batchDestroy']); // 批量删除
    });

    // 图片上传相关
    Route::prefix('images')->group(function () {
        Route::post('upload', [ImageController::class, 'upload']);         // 上传图片
        Route::get('/', [ImageController::class, 'index']);                // 获取图片列表
        Route::delete('/', [ImageController::class, 'delete']);            // 删除图片
    });

    // 用户管理相关（仅管理员可访问）
    Route::middleware('admin')->prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);                 // 获取用户列表
        Route::post('/', [UserController::class, 'store']);                // 创建用户
        Route::get('statistics', [UserController::class, 'statistics']);   // 获取用户统计
        Route::get('{id}', [UserController::class, 'show']);               // 获取用户详情
        Route::put('{id}', [UserController::class, 'update']);             // 更新用户
        Route::delete('{id}', [UserController::class, 'destroy']);         // 删除用户
        Route::delete('batch', [UserController::class, 'batchDestroy']);   // 批量删除用户
        Route::put('{id}/reset-password', [UserController::class, 'resetPassword']); // 重置密码
        Route::put('{id}/toggle-role', [UserController::class, 'toggleRole']); // 切换角色
    });
});