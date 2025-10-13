<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 图片路由 - 直接处理，不使用中间件
Route::get('/images/{path}', [ImageController::class, 'show'])
    ->where('path', '.*');

Route::options('/images/{path}', [ImageController::class, 'show'])
    ->where('path', '.*');

// 添加一个测试路由来验证CORS
Route::get('/test-cors', function () {
    return response()->json(['message' => 'CORS test'])
        ->header('Access-Control-Allow-Origin', 'https://collect.wlai.vip')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Origin, Accept')
        ->header('Access-Control-Allow-Credentials', 'true');
});

Route::get('/', function () {
    return view('welcome');
});
