<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

Route::get('/', function () {
    return view('welcome');
});

// 图片访问路由，处理CORS问题
Route::match(['GET', 'OPTIONS'], '/images/{path}', [ImageController::class, 'show'])
    ->where('path', '.*')
    ->name('images.show');
