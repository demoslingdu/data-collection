<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * 统一API响应格式封装类
 * 提供标准化的API响应格式，确保所有接口返回格式一致
 */
class ApiResponse
{
    /**
     * 成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 响应消息
     * @param int $code HTTP状态码
     * @return JsonResponse
     */
    public static function success($data = null, string $message = '操作成功', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * 失败响应
     *
     * @param string $message 错误消息
     * @param int $code HTTP状态码
     * @param mixed $errors 详细错误信息
     * @return JsonResponse
     */
    public static function error(string $message = '操作失败', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * 验证失败响应
     *
     * @param mixed $errors 验证错误信息
     * @param string $message 错误消息
     * @return JsonResponse
     */
    public static function validationError($errors, string $message = '验证失败'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }

    /**
     * 未授权响应
     *
     * @param string $message 错误消息
     * @return JsonResponse
     */
    public static function unauthorized(string $message = '未授权访问'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * 禁止访问响应
     *
     * @param string $message 错误消息
     * @return JsonResponse
     */
    public static function forbidden(string $message = '禁止访问'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * 资源未找到响应
     *
     * @param string $message 错误消息
     * @return JsonResponse
     */
    public static function notFound(string $message = '资源未找到'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * 服务器内部错误响应
     *
     * @param string $message 错误消息
     * @param mixed $errors 详细错误信息
     * @return JsonResponse
     */
    public static function serverError(string $message = '服务器内部错误', $errors = null): JsonResponse
    {
        return self::error($message, 500, $errors);
    }

    /**
     * 创建成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 响应消息
     * @return JsonResponse
     */
    public static function created($data = null, string $message = '创建成功'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * 更新成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 响应消息
     * @return JsonResponse
     */
    public static function updated($data = null, string $message = '更新成功'): JsonResponse
    {
        return self::success($data, $message, 200);
    }

    /**
     * 删除成功响应
     *
     * @param string $message 响应消息
     * @return JsonResponse
     */
    public static function deleted(string $message = '删除成功'): JsonResponse
    {
        return self::success(null, $message, 200);
    }

    /**
     * 分页数据响应
     *
     * @param mixed $data 分页数据
     * @param string $message 响应消息
     * @return JsonResponse
     */
    public static function paginated($data, string $message = '获取数据成功'): JsonResponse
    {
        return self::success($data, $message, 200);
    }
}