<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * 用户资源类
 * 用于格式化API响应中的用户时间字段为北京时间
 */
class UserResource extends JsonResource
{
    /**
     * 将资源转换为数组
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'account' => $this->account,
            'role' => $this->role,
            // 将时间转换为北京时间格式 (UTC+8)
            'created_at' => $this->formatToBeijingTime($this->created_at),
            'updated_at' => $this->formatToBeijingTime($this->updated_at),
            'company_id' => $this->company_id,
        ];
    }

    /**
     * 将时间格式化为北京时间
     * 格式：2025-10-20 13:30:29（简洁的日期时间格式）
     * 
     * @param \Carbon\Carbon|null $dateTime
     * @return string|null
     */
    private function formatToBeijingTime($dateTime): ?string
    {
        if (!$dateTime) {
            return null;
        }

        // 将时间转换为北京时区并格式化为简洁的日期时间格式
        return $dateTime->setTimezone('Asia/Shanghai')->format('Y-m-d H:i:s');
    }
}