<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * 图片CORS中间件
 * 专门处理图片路由的跨域请求
 */
class ImageCorsMiddleware
{
    /**
     * 处理传入的请求
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 记录中间件被调用
        Log::info('ImageCorsMiddleware called', [
            'method' => $request->method(),
            'path' => $request->path(),
            'origin' => $request->header('Origin'),
            'timestamp' => now()->toDateTimeString()
        ]);

        // 允许的源域名
        $allowedOrigins = [
            'https://collect.wlai.vip',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];

        $origin = $request->header('Origin');

        // 处理预检请求 (OPTIONS)
        if ($request->getMethod() === 'OPTIONS') {
            Log::info('Handling OPTIONS preflight request in middleware');
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // 设置CORS头部
        if ($origin && in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            Log::info('Set CORS origin to: ' . $origin);
        } else {
            // 如果没有Origin头部或不在允许列表中，设置为第一个允许的域名
            $response->headers->set('Access-Control-Allow-Origin', 'https://collect.wlai.vip');
            Log::info('Set default CORS origin to: https://collect.wlai.vip');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Origin, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');

        Log::info('CORS headers set', [
            'Access-Control-Allow-Origin' => $response->headers->get('Access-Control-Allow-Origin'),
            'Access-Control-Allow-Methods' => $response->headers->get('Access-Control-Allow-Methods')
        ]);

        return $response;
    }
}