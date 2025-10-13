<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 图片控制器
 * 处理图片访问请求并设置CORS头部
 */
class ImageController extends Controller
{
    /**
     * 显示指定的图片文件
     * 
     * @param Request $request
     * @param string $path 图片路径
     * @return BinaryFileResponse|Response
     */
    public function show(Request $request, $path)
    {
        // 处理OPTIONS预检请求
        if ($request->getMethod() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'https://collect.wlai.vip')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*')
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        // 构建完整的文件路径
        $fullPath = public_path('images/' . $path);
        
        // 检查文件是否存在
        if (!file_exists($fullPath)) {
            return response('File not found', 404)
                ->header('Access-Control-Allow-Origin', 'https://collect.wlai.vip')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*')
                ->header('Access-Control-Allow-Credentials', 'true');
        }

        // 获取文件的MIME类型
        $mimeType = mime_content_type($fullPath);
        
        // 创建文件响应并设置CORS头部
        $response = response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Access-Control-Allow-Origin' => 'https://collect.wlai.vip',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Credentials' => 'true',
        ]);

        return $response;
    }
}