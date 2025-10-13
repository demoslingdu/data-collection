<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

/**
 * 公司数据填充器
 */
class CompanySeeder extends Seeder
{
    /**
     * 运行数据填充
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => '北京科技有限公司',
                'code' => 'BJ001',
                'description' => '专注于数据处理和分析的科技公司',
                'contact_person' => '张经理',
                'contact_phone' => '010-12345678',
                'contact_email' => 'zhang@bjtech.com',
                'is_active' => true,
            ],
            [
                'name' => '上海数据服务公司',
                'code' => 'SH001',
                'description' => '提供专业数据服务解决方案',
                'contact_person' => '李总监',
                'contact_phone' => '021-87654321',
                'contact_email' => 'li@shdata.com',
                'is_active' => true,
            ],
            [
                'name' => '深圳智能科技',
                'code' => 'SZ001',
                'description' => '人工智能和数据挖掘技术公司',
                'contact_person' => '王主管',
                'contact_phone' => '0755-11223344',
                'contact_email' => 'wang@szai.com',
                'is_active' => true,
            ],
            [
                'name' => '广州信息技术',
                'code' => 'GZ001',
                'description' => '信息技术服务提供商',
                'contact_person' => '陈经理',
                'contact_phone' => '020-55667788',
                'contact_email' => 'chen@gzit.com',
                'is_active' => false, // 测试禁用状态
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}