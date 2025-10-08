<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * 图片上传控制器 - 专为安卓应用优化
 * 
 * 提供图片上传功能，支持多种图片格式，包含压缩、缩略图生成等功能
 * 上传的图片将按日期分目录保存到 public/images 目录下
 */
class ImageController extends Controller
{
    /**
     * 支持的图片格式
     */
    private const SUPPORTED_FORMATS = [
        'jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'tif'
    ];

    /**
     * 图片质量设置
     */
    private const IMAGE_QUALITY = 85;

    /**
     * 缩略图尺寸
     */
    private const THUMBNAIL_SIZE = 300;

    /**
     * 上传图片 - 支持普通文件上传和Base64上传
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            // 检查是否为批量上传
            if ($request->has('images') && is_array($request->input('images'))) {
                return $this->batchUpload($request);
            }

            // 检查是否为Base64上传
            if ($request->has('base64_image')) {
                return $this->uploadBase64($request);
            }

            // 普通文件上传验证
            $request->validate([
                'image' => [
                    'required',
                    'file',
                    'image',
                    'mimes:' . implode(',', self::SUPPORTED_FORMATS),
                    'max:10240' // 10MB = 10240KB
                ]
            ], [
                'image.required' => '请选择要上传的图片',
                'image.file' => '上传的文件无效',
                'image.image' => '文件必须是图片格式',
                'image.mimes' => '图片格式必须是：' . implode(', ', self::SUPPORTED_FORMATS),
                'image.max' => '图片大小不能超过10MB'
            ]);

            $file = $request->file('image');
            
            // 增强安全性检测
            if (!$this->isValidImageFile($file)) {
                return response()->json([
                    'success' => false,
                    'message' => '文件安全检测失败，请上传有效的图片文件',
                    'error_code' => 'INVALID_FILE_TYPE'
                ], 422);
            }

            // 处理图片上传
            $result = $this->processImageUpload($file);
            
            return response()->json([
                'success' => true,
                'message' => '图片上传成功',
                'data' => $result
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '图片上传失败',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
            
        } catch (\Exception $e) {
            \Log::error('图片上传失败: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '图片上传失败，服务器内部错误',
                'error_code' => 'SERVER_ERROR',
                'debug_message' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * 处理图片上传的核心方法
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    private function processImageUpload($file): array
    {
        // 创建按日期分组的目录结构
        $dateFolder = date('Y/m/d');
        $imagesPath = public_path('images/' . $dateFolder);
        
        if (!file_exists($imagesPath)) {
            mkdir($imagesPath, 0755, true);
        }

        // 生成唯一的文件名
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid() . '.' . $extension;
        $fullPath = $imagesPath . '/' . $filename;

        // 使用 Intervention Image 处理图片
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getPathname());

        // 获取原始图片信息
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $originalSize = $file->getSize();

        // 压缩并保存主图片
        if ($extension === 'png') {
            // PNG 格式保持透明度
            $encoded = $image->toPng();
        } elseif ($extension === 'webp') {
            // WebP 格式
            $encoded = $image->toWebp(quality: self::IMAGE_QUALITY);
        } else {
            // JPEG 格式进行质量压缩
            $encoded = $image->toJpeg(quality: self::IMAGE_QUALITY);
        }
        
        // 保存主图片文件
        file_put_contents($fullPath, $encoded);

        // 生成缩略图
        $thumbnailFilename = 'thumb_' . $filename;
        $thumbnailPath = $imagesPath . '/' . $thumbnailFilename;
        
        $thumbnail = clone $image;
        $thumbnail->scale(width: self::THUMBNAIL_SIZE);
        
        // 编码并保存缩略图
        if ($extension === 'png') {
            $thumbnailEncoded = $thumbnail->toPng();
        } elseif ($extension === 'webp') {
            $thumbnailEncoded = $thumbnail->toWebp(quality: self::IMAGE_QUALITY);
        } else {
            $thumbnailEncoded = $thumbnail->toJpeg(quality: self::IMAGE_QUALITY);
        }
        
        // 保存缩略图文件
        file_put_contents($thumbnailPath, $thumbnailEncoded);

        // 获取压缩后的文件大小
        $compressedSize = filesize($fullPath);
        $thumbnailSize = filesize($thumbnailPath);

        // 生成访问URL
        $baseUrl = url('images/' . $dateFolder . '/');
        $imageUrl = $baseUrl .'/'. $filename;
        $thumbnailUrl = $baseUrl . $thumbnailFilename;

        return [
            'filename' => $filename,
            'path' => $dateFolder . '/' . $filename,
            'url' => $imageUrl,
            'thumbnail' => [
                'filename' => $thumbnailFilename,
                'url' => $thumbnailUrl,
                'size' => $thumbnailSize
            ],
            'dimensions' => [
                'width' => $originalWidth,
                'height' => $originalHeight
            ],
            'size' => [
                'original' => $originalSize,
                'compressed' => $compressedSize,
                'compression_ratio' => round((1 - $compressedSize / $originalSize) * 100, 2)
            ],
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'created_at' => now()->toISOString()
        ];
    }

    /**
     * 验证图片文件的安全性
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return bool
     */
    private function isValidImageFile($file): bool
    {
        // 检查文件扩展名
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            return false;
        }

        // 检查 MIME 类型
        $allowedMimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
            'image/webp', 'image/bmp', 'image/tiff', 'image/tif'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        // 使用 getimagesize 进一步验证
        $imageInfo = @getimagesize($file->getPathname());
        if ($imageInfo === false) {
            return false;
        }

        // 检查图片类型常量
        $allowedTypes = [
            IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, 
            IMAGETYPE_WEBP, IMAGETYPE_BMP, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM
        ];

        return in_array($imageInfo[2], $allowedTypes);
    }

    /**
     * Base64 图片上传
     * 
     * @param Request $request
     * @return JsonResponse
     */
    private function uploadBase64(Request $request): JsonResponse
    {
        $request->validate([
            'base64_image' => 'required|string',
            'filename' => 'nullable|string'
        ], [
            'base64_image.required' => '请提供Base64图片数据'
        ]);

        $base64Data = $request->input('base64_image');
        $customFilename = $request->input('filename');

        // 解析 Base64 数据
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $extension = $matches[1];
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Base64数据格式无效',
                'error_code' => 'INVALID_BASE64_FORMAT'
            ], 422);
        }

        // 验证扩展名
        if (!in_array($extension, self::SUPPORTED_FORMATS)) {
            return response()->json([
                'success' => false,
                'message' => '不支持的图片格式',
                'error_code' => 'UNSUPPORTED_FORMAT'
            ], 422);
        }

        // 解码 Base64
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return response()->json([
                'success' => false,
                'message' => 'Base64解码失败',
                'error_code' => 'BASE64_DECODE_ERROR'
            ], 422);
        }

        // 检查文件大小（10MB限制）
        if (strlen($imageData) > 10 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => '图片大小不能超过10MB',
                'error_code' => 'FILE_TOO_LARGE'
            ], 422);
        }

        // 创建临时文件进行处理
        $tempFile = tempnam(sys_get_temp_dir(), 'base64_image');
        file_put_contents($tempFile, $imageData);

        try {
            // 验证图片
            $imageInfo = @getimagesize($tempFile);
            if ($imageInfo === false) {
                unlink($tempFile);
                return response()->json([
                    'success' => false,
                    'message' => '无效的图片数据',
                    'error_code' => 'INVALID_IMAGE_DATA'
                ], 422);
            }

            // 处理图片上传
            $result = $this->processBase64Image($tempFile, $extension, $customFilename);
            unlink($tempFile);

            return response()->json([
                'success' => true,
                'message' => 'Base64图片上传成功',
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            unlink($tempFile);
            throw $e;
        }
    }

    /**
     * 处理 Base64 图片
     * 
     * @param string $tempFile
     * @param string $extension
     * @param string|null $customFilename
     * @return array
     */
    private function processBase64Image(string $tempFile, string $extension, ?string $customFilename = null): array
    {
        // 创建按日期分组的目录结构
        $dateFolder = date('Y/m/d');
        $imagesPath = public_path('images/' . $dateFolder);
        
        if (!file_exists($imagesPath)) {
            mkdir($imagesPath, 0755, true);
        }

        // 生成文件名
        $filename = $customFilename ? 
            pathinfo($customFilename, PATHINFO_FILENAME) . '.' . $extension :
            Str::uuid() . '.' . $extension;
        
        $fullPath = $imagesPath . '/' . $filename;

        // 使用 Intervention Image 处理图片
        $manager = new ImageManager(new Driver());
        $image = $manager->read($tempFile);

        // 获取图片信息
        $originalWidth = $image->width();
        $originalHeight = $image->height();
        $originalSize = filesize($tempFile);

        // 压缩并保存主图片
        if ($extension === 'png') {
            // PNG 格式保持透明度
            $encoded = $image->toPng();
        } elseif ($extension === 'webp') {
            // WebP 格式
            $encoded = $image->toWebp(quality: self::IMAGE_QUALITY);
        } else {
            // JPEG 格式进行质量压缩
            $encoded = $image->toJpeg(quality: self::IMAGE_QUALITY);
        }
        
        // 保存主图片文件
        file_put_contents($fullPath, $encoded);

        // 生成缩略图
        $thumbnailFilename = 'thumb_' . $filename;
        $thumbnailPath = $imagesPath . '/' . $thumbnailFilename;
        
        $thumbnail = clone $image;
        $thumbnail->scale(width: self::THUMBNAIL_SIZE);
        
        // 编码并保存缩略图
        if ($extension === 'png') {
            $thumbnailEncoded = $thumbnail->toPng();
        } elseif ($extension === 'webp') {
            $thumbnailEncoded = $thumbnail->toWebp(quality: self::IMAGE_QUALITY);
        } else {
            $thumbnailEncoded = $thumbnail->toJpeg(quality: self::IMAGE_QUALITY);
        }
        
        // 保存缩略图文件
        file_put_contents($thumbnailPath, $thumbnailEncoded);

        // 获取压缩后的文件大小
        $compressedSize = filesize($fullPath);
        $thumbnailSize = filesize($thumbnailPath);

        // 生成访问URL
        $baseUrl = url('images/' . $dateFolder . '/');
        $imageUrl = $baseUrl . $filename;
        $thumbnailUrl = $baseUrl . $thumbnailFilename;

        return [
            'filename' => $filename,
            'path' => $dateFolder . '/' . $filename,
            'url' => $imageUrl,
            'thumbnail' => [
                'filename' => $thumbnailFilename,
                'url' => $thumbnailUrl,
                'size' => $thumbnailSize
            ],
            'dimensions' => [
                'width' => $originalWidth,
                'height' => $originalHeight
            ],
            'size' => [
                'original' => $originalSize,
                'compressed' => $compressedSize,
                'compression_ratio' => round((1 - $compressedSize / $originalSize) * 100, 2)
            ],
            'mime_type' => 'image/' . $extension,
            'extension' => $extension,
            'upload_type' => 'base64',
            'created_at' => now()->toISOString()
        ];
    }

    /**
     * 批量上传图片
     * 
     * @param Request $request
     * @return JsonResponse
     */
    private function batchUpload(Request $request): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => [
                'required',
                'file',
                'image',
                'mimes:' . implode(',', self::SUPPORTED_FORMATS),
                'max:10240'
            ]
        ], [
            'images.required' => '请选择要上传的图片',
            'images.array' => '图片数据格式错误',
            'images.max' => '一次最多只能上传10张图片',
            'images.*.required' => '图片文件不能为空',
            'images.*.file' => '上传的文件无效',
            'images.*.image' => '文件必须是图片格式',
            'images.*.mimes' => '图片格式必须是：' . implode(', ', self::SUPPORTED_FORMATS),
            'images.*.max' => '单张图片大小不能超过10MB'
        ]);

        $results = [];
        $errors = [];

        foreach ($request->file('images') as $index => $file) {
            try {
                // 安全性检测
                if (!$this->isValidImageFile($file)) {
                    $errors[] = [
                        'index' => $index,
                        'message' => '文件安全检测失败',
                        'filename' => $file->getClientOriginalName()
                    ];
                    continue;
                }

                // 处理单个图片
                $result = $this->processImageUpload($file);
                $results[] = $result;

            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'message' => '上传失败: ' . $e->getMessage(),
                    'filename' => $file->getClientOriginalName()
                ];
            }
        }

        return response()->json([
            'success' => count($results) > 0,
            'message' => sprintf('批量上传完成，成功：%d张，失败：%d张', count($results), count($errors)),
            'data' => [
                'successful' => $results,
                'failed' => $errors,
                'summary' => [
                    'total' => count($request->file('images')),
                    'successful_count' => count($results),
                    'failed_count' => count($errors)
                ]
            ]
        ], count($results) > 0 ? 200 : 422);
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
                'path' => 'required|string'
            ], [
                'path.required' => '请提供要删除的文件路径'
            ]);

            $path = $request->input('path');
            $fullPath = public_path('images/' . $path);
            $thumbnailPath = dirname($fullPath) . '/thumb_' . basename($fullPath);
            
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => '文件不存在',
                    'error_code' => 'FILE_NOT_FOUND'
                ], 404);
            }
            
            // 删除主图片
            unlink($fullPath);
            
            // 删除缩略图（如果存在）
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
            
            return response()->json([
                'success' => true,
                'message' => '图片删除成功'
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '删除失败：' . $e->getMessage(),
                'error_code' => 'SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * 获取图片列表 - 支持分页和筛选
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'date' => 'nullable|date_format:Y-m-d',
                'extension' => 'nullable|string|in:' . implode(',', self::SUPPORTED_FORMATS)
            ]);

            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $filterDate = $request->input('date');
            $filterExtension = $request->input('extension');

            $imagesPath = public_path('images');
            
            if (!file_exists($imagesPath)) {
                return response()->json([
                    'success' => true,
                    'message' => '获取图片列表成功',
                    'data' => [
                        'images' => [],
                        'pagination' => [
                            'current_page' => $page,
                            'per_page' => $perPage,
                            'total' => 0,
                            'total_pages' => 0
                        ]
                    ]
                ], 200);
            }
            
            $images = $this->scanImagesRecursively($imagesPath, $filterDate, $filterExtension);
            
            // 排序（按创建时间倒序）
            usort($images, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // 分页处理
            $total = count($images);
            $totalPages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            $paginatedImages = array_slice($images, $offset, $perPage);
            
            return response()->json([
                'success' => true,
                'message' => '获取图片列表成功',
                'data' => [
                    'images' => $paginatedImages,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'total_pages' => $totalPages,
                        'has_next_page' => $page < $totalPages,
                        'has_prev_page' => $page > 1
                    ],
                    'filters' => [
                        'date' => $filterDate,
                        'extension' => $filterExtension
                    ]
                ]
            ], 200);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '参数验证失败',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取图片列表失败：' . $e->getMessage(),
                'error_code' => 'SERVER_ERROR'
            ], 500);
        }
    }

    /**
     * 递归扫描图片目录
     * 
     * @param string $path
     * @param string|null $filterDate
     * @param string|null $filterExtension
     * @return array
     */
    private function scanImagesRecursively(string $path, ?string $filterDate = null, ?string $filterExtension = null): array
    {
        $images = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && !str_starts_with($file->getFilename(), 'thumb_')) {
                $extension = strtolower($file->getExtension());
                
                // 扩展名筛选
                if ($filterExtension && $extension !== $filterExtension) {
                    continue;
                }
                
                // 检查是否为支持的图片格式
                if (!in_array($extension, self::SUPPORTED_FORMATS)) {
                    continue;
                }

                $relativePath = str_replace(public_path('images/'), '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath); // Windows 路径兼容
                
                // 日期筛选
                if ($filterDate) {
                    $fileDate = date('Y-m-d', $file->getMTime());
                    if ($fileDate !== $filterDate) {
                        continue;
                    }
                }

                // 检查缩略图
                $thumbnailPath = dirname($file->getPathname()) . '/thumb_' . $file->getFilename();
                $hasThumbnail = file_exists($thumbnailPath);

                // 获取图片尺寸
                $imageInfo = @getimagesize($file->getPathname());
                $dimensions = $imageInfo ? [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ] : null;

                $images[] = [
                    'filename' => $file->getFilename(),
                    'path' => $relativePath,
                    'url' => url('images/' . $relativePath),
                    'thumbnail' => $hasThumbnail ? [
                        'url' => url('images/' . dirname($relativePath) . '/thumb_' . $file->getFilename()),
                        'size' => filesize($thumbnailPath)
                    ] : null,
                    'size' => $file->getSize(),
                    'extension' => $extension,
                    'mime_type' => $imageInfo ? $imageInfo['mime'] : null,
                    'dimensions' => $dimensions,
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    'human_readable_size' => $this->formatBytes($file->getSize())
                ];
            }
        }

        return $images;
    }

    /**
     * 格式化文件大小为人类可读格式
     * 
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 获取图片统计信息
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $imagesPath = public_path('images');
            
            if (!file_exists($imagesPath)) {
                return response()->json([
                    'success' => true,
                    'message' => '获取统计信息成功',
                    'data' => [
                        'total_images' => 0,
                        'total_size' => 0,
                        'by_extension' => [],
                        'by_date' => [],
                        'storage_usage' => '0 B'
                    ]
                ], 200);
            }

            $images = $this->scanImagesRecursively($imagesPath);
            
            $statistics = [
                'total_images' => count($images),
                'total_size' => array_sum(array_column($images, 'size')),
                'by_extension' => [],
                'by_date' => [],
                'average_size' => 0
            ];

            // 按扩展名统计
            foreach ($images as $image) {
                $ext = $image['extension'];
                if (!isset($statistics['by_extension'][$ext])) {
                    $statistics['by_extension'][$ext] = ['count' => 0, 'size' => 0];
                }
                $statistics['by_extension'][$ext]['count']++;
                $statistics['by_extension'][$ext]['size'] += $image['size'];
            }

            // 按日期统计
            foreach ($images as $image) {
                $date = date('Y-m-d', strtotime($image['created_at']));
                if (!isset($statistics['by_date'][$date])) {
                    $statistics['by_date'][$date] = ['count' => 0, 'size' => 0];
                }
                $statistics['by_date'][$date]['count']++;
                $statistics['by_date'][$date]['size'] += $image['size'];
            }

            // 计算平均大小
            if ($statistics['total_images'] > 0) {
                $statistics['average_size'] = round($statistics['total_size'] / $statistics['total_images'], 2);
            }

            // 格式化存储使用量
            $statistics['storage_usage'] = $this->formatBytes($statistics['total_size']);
            $statistics['average_size_formatted'] = $this->formatBytes($statistics['average_size']);

            return response()->json([
                'success' => true,
                'message' => '获取统计信息成功',
                'data' => $statistics
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '获取统计信息失败：' . $e->getMessage(),
                'error_code' => 'SERVER_ERROR'
            ], 500);
        }
    }
}