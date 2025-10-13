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
        // 设置完整的CORS头部
        $corsHeaders = [
            'Access-Control-Allow-Origin' => 'https://collect.wlai.vip',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => '*',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
        ];

        // 处理OPTIONS预检请求
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', 'https://collect.wlai.vip')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', '*')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Content-Type', 'text/plain')
                ->header('Content-Length', '0');
        }

        // 构建完整的文件路径
        $fullPath = public_path('images/' . $path);
        
        // 检查文件是否存在
        if (!file_exists($fullPath)) {
            return response('File not found', 404, $corsHeaders);
        }

        // 获取文件的MIME类型
        $mimeType = mime_content_type($fullPath);
        
        // 读取文件内容
        $fileContent = file_get_contents($fullPath);
        
        // 创建响应并设置所有头部
        $response = response($fileContent, 200, array_merge([
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($fileContent),
        ], $corsHeaders));

        return $response;
    }
}