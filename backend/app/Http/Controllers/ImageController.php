<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    /**
     * 显示指定的图片
     * 
     * @param Request $request
     * @param string $path
     * @return Response
     */
    public function show(Request $request, $path)
    {
        // 记录请求信息
        Log::info('ImageController show method called', [
            'method' => $request->method(),
            'path' => $path,
            'origin' => $request->header('Origin'),
            'user_agent' => $request->header('User-Agent'),
            'full_url' => $request->fullUrl(),
            'timestamp' => now()->toDateTimeString()
        ]);

        // 处理 OPTIONS 预检请求
        if ($request->method() === 'OPTIONS') {
            Log::info('Handling OPTIONS preflight request');
            return $this->createCorsResponse('', 200);
        }

        // 构建图片文件路径
        $imagePath = public_path('images/' . $path);
        
        // 检查文件是否存在
        if (!file_exists($imagePath)) {
            Log::warning('Image file not found', ['path' => $imagePath]);
            return $this->createCorsResponse('Image not found', 404);
        }

        // 获取文件内容和MIME类型
        $fileContent = file_get_contents($imagePath);
        $mimeType = mime_content_type($imagePath);
        
        Log::info('Returning image response', [
            'path' => $imagePath,
            'mime_type' => $mimeType,
            'size' => strlen($fileContent)
        ]);

        // 创建响应并设置CORS头部
        $response = response($fileContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Length' => strlen($fileContent),
        ]);

        // 手动设置CORS头部
        $response->header('Access-Control-Allow-Origin', 'https://collect.wlai.vip');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Origin, Accept');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', '86400');

        Log::info('CORS headers manually set on response');

        return $response;
    }

    /**
     * 创建带有CORS头部的响应
     * 
     * @param string $content
     * @param int $status
     * @param array $headers
     * @return Response
     */
    private function createCorsResponse($content, $status, $headers = [])
    {
        // 创建响应
        $response = response($content, $status, $headers);

        // 手动设置CORS头部
        $response->header('Access-Control-Allow-Origin', 'https://collect.wlai.vip');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Origin, Accept');
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Max-Age', '86400');

        Log::info('Creating CORS response', [
            'status' => $status,
            'cors_origin' => 'https://collect.wlai.vip'
        ]);

        return $response;
    }
}