<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

/**
 * 数据记录资源类
 * 用于格式化API响应中的时间字段为北京时间
 */
class DataRecordResource extends JsonResource
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
            'image_url' => $this->image_url,
            'chat_images' => $this->chat_images,
            'submitter_id' => $this->submitter_id,
            'platform' => $this->platform,
            'platform_id' => $this->platform_id,
            'is_claimed' => $this->is_claimed,
            'is_completed' => $this->is_completed,
            'claimer_id' => $this->claimer_id,
            'is_duplicate' => $this->is_duplicate,
            // 将时间转换为北京时间格式 (UTC+8)
            'created_at' => $this->formatToBeijingTime($this->created_at),
            'updated_at' => $this->formatToBeijingTime($this->updated_at),
            'phone' => $this->phone,
            'synced_to_external' => $this->synced_to_external,
            // 关联模型也需要格式化时间
            'submitter' => $this->whenLoaded('submitter', function () {
                return new UserResource($this->submitter);
            }),
            'claimer' => $this->whenLoaded('claimer', function () {
                return new UserResource($this->claimer);
            }),
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