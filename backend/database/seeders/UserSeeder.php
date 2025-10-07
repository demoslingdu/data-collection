<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * 初始化用户数据
     */
    public function run(): void
    {
        // 创建系统管理员
        DB::table('users')->insert([
            'name' => '系统管理员',
            'account' => 'admin',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 创建测试普通用户
        DB::table('users')->insert([
            'name' => '测试用户',
            'account' => 'testuser',
            'password' => Hash::make('user123'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 创建更多测试用户
        DB::table('users')->insert([
            'name' => '张三',
            'account' => 'zhangsan',
            'password' => Hash::make('123456'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => '李四',
            'account' => 'lisi',
            'password' => Hash::make('123456'),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'name' => '王五',
            'account' => 'wangwu',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
