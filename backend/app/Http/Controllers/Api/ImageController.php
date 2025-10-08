<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 图片上传控制器
 * 
 * 提供图片上传功能，支持多种图片格式
 * 上传的图片将保存到 public/images 目录下
 */
class ImageController extends Controller
{
    /**
     * 上传图片
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // 验证上传的文件
            $request->validate([
                'image' => [
                    'required',
                    'file',
                    'image',
                    'mimes:jpeg,jpg,png,gif,webp',
                    'max:5120' // 5MB = 5120KB
                ]
            ], [
                'image.required' => '请选择要上传的图片',
                'image.file' => '上传的文件无效',
                'image.image' => '文件必须是图片格式',
                'image.mimes' => '图片格式必须是：jpeg, jpg, png, gif, webp',
                'image.max' => '图片大小不能超过5MB'
            ]);

            $file = $request->file('image');
            
            // 生成唯一的文件名
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // 确保 images 目录存在
            $imagesPath = public_path('images');
            if (!file_exists($imagesPath)) {
                mkdir($imagesPath, 0755, true);
            }
            
            // 移动文件到 public/images 目录
            $file->move($imagesPath, $filename);
            
            // 生成访问URL
            $url = url('images/' . $filename);
            
            return response()->json([
                'success' => true,
                'message' => '图片上传成功',
                'data' => [
                    'filename' => $filename,
                    'url' => $url,
                    'size' => $file->getSize(),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType()
                ]
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '图片上传失败',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '图片上传失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 删除图片
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'filename' => 'required|string'
            ], [
                'filename.required' => '请提供要删除的文件名'
            ]);

            $filename = $request->input('filename');
            $filePath = public_path('images/' . $filename);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => '文件不存在'
                ], 404);
            }
            
            // 删除文件
            unlink($filePath);
            
            return response()->json([
                'success' => true,
                'message' => '图片删除成功'
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败：' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 获取图片列表
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $imagesPath = public_path('images');
            
            if (!file_exists($imagesPath)) {
                return response()->json([
                    'success' => true,
                    'message' => '获取图片列表成功',
                    'data' => []
                ], 200);
            }
            
            $files = scandir($imagesPath);
            $images = [];
            
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && is_file($imagesPath . '/' . $file)) {
                    $filePath = $imagesPath . '/' . $file;
                    $images[] = [
                        'filename' => $file,
                        'url' => url('images/' . $file),
                        'size' => filesize($filePath),
                        'created_at' => date('Y-m-d H:i:s', filemtime($filePath))
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => '获取图片列表成功',
                'data' => $images
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取图片列表失败：' . $e->getMessage()
            ], 500);
        }
    }
}