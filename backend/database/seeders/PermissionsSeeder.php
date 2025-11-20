<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // 图书管理
            ['name' => 'books.view', 'display_name' => '查看图书管理', 'group' => 'books', 'description' => '访问后台图书管理列表页面'],
            ['name' => 'books.create', 'display_name' => '创建图书', 'group' => 'books', 'description' => '创建新图书'],
            ['name' => 'books.edit', 'display_name' => '编辑图书', 'group' => 'books', 'description' => '编辑图书信息'],
            ['name' => 'books.delete', 'display_name' => '删除图书', 'group' => 'books', 'description' => '删除图书'],
            ['name' => 'books.upload', 'display_name' => '上传图书文件', 'group' => 'books', 'description' => '上传图书文件（暂不适用，请使用files.upload）'],
            ['name' => 'books.download', 'display_name' => '下载图书文件', 'group' => 'books', 'description' => '下载图书附件'],

            // 作者管理
            ['name' => 'authors.view', 'display_name' => '查看作者管理', 'group' => 'authors', 'description' => '访问后台作者管理列表页面'],
            ['name' => 'authors.create', 'display_name' => '创建作者', 'group' => 'authors', 'description' => '创建新作者'],
            ['name' => 'authors.edit', 'display_name' => '编辑作者', 'group' => 'authors', 'description' => '编辑作者信息'],
            ['name' => 'authors.delete', 'display_name' => '删除作者', 'group' => 'authors', 'description' => '删除作者'],

            // 标签管理
            ['name' => 'tags.view', 'display_name' => '查看标签管理', 'group' => 'tags', 'description' => '访问后台标签管理列表页面'],
            ['name' => 'tags.create', 'display_name' => '创建标签', 'group' => 'tags', 'description' => '创建新标签'],
            ['name' => 'tags.edit', 'display_name' => '编辑标签', 'group' => 'tags', 'description' => '编辑标签信息'],
            ['name' => 'tags.delete', 'display_name' => '删除标签', 'group' => 'tags', 'description' => '删除标签'],

            // 丛书管理
            ['name' => 'series.view', 'display_name' => '查看丛书管理', 'group' => 'series', 'description' => '访问后台丛书管理列表页面'],
            ['name' => 'series.create', 'display_name' => '创建丛书', 'group' => 'series', 'description' => '创建新丛书'],
            ['name' => 'series.edit', 'display_name' => '编辑丛书', 'group' => 'series', 'description' => '编辑丛书信息'],
            ['name' => 'series.delete', 'display_name' => '删除丛书', 'group' => 'series', 'description' => '删除丛书'],

            // 书架管理
            ['name' => 'shelves.view', 'display_name' => '查看书架管理', 'group' => 'shelves', 'description' => '访问后台书架管理列表页面'],
            ['name' => 'shelves.create', 'display_name' => '创建书架', 'group' => 'shelves', 'description' => '创建自己的书架'],
            ['name' => 'shelves.edit', 'display_name' => '编辑书架', 'group' => 'shelves', 'description' => '编辑自己的书架'],
            ['name' => 'shelves.delete', 'display_name' => '删除书架', 'group' => 'shelves', 'description' => '删除自己的书架'],
            ['name' => 'shelves.create_global', 'display_name' => '创建全局书架', 'group' => 'shelves', 'description' => '创建/设置全局书架'],
            ['name' => 'shelves.create_public', 'display_name' => '创建公开书架', 'group' => 'shelves', 'description' => '创建/设置公开书架'],
            ['name' => 'shelves.manage_all', 'display_name' => '管理所有书架', 'group' => 'shelves', 'description' => '管理所有用户的书架'],

            // 文件管理
            ['name' => 'files.view', 'display_name' => '查看文件列表', 'group' => 'files', 'description' => '查看所有文件列表'],
            ['name' => 'files.upload', 'display_name' => '上传文件', 'group' => 'files', 'description' => '上传文件'],
            ['name' => 'files.delete', 'display_name' => '删除文件', 'group' => 'files', 'description' => '删除文件'],
            ['name' => 'files.manage_all', 'display_name' => '管理所有文件', 'group' => 'files', 'description' => '管理所有文件（暂不适用，当前文件管理请使用files.view）'],
            ['name' => 'files.cleanup', 'display_name' => '清理未使用文件', 'group' => 'files', 'description' => '清理未使用的文件'],

            // 用户管理
            ['name' => 'users.view', 'display_name' => '查看用户列表', 'group' => 'users', 'description' => '查看用户列表'],
            ['name' => 'users.create', 'display_name' => '创建用户', 'group' => 'users', 'description' => '创建新用户'],
            ['name' => 'users.edit', 'display_name' => '编辑用户', 'group' => 'users', 'description' => '编辑用户信息'],
            ['name' => 'users.delete', 'display_name' => '删除用户', 'group' => 'users', 'description' => '删除用户'],
            ['name' => 'users.assign_roles', 'display_name' => '分配角色', 'group' => 'users', 'description' => '给用户分配角色'],
            ['name' => 'users.manage_all', 'display_name' => '管理所有用户', 'group' => 'users', 'description' => '管理所有用户（暂不适用，请根据具体需求分配权限，当前可管理的为优先级不高于自身的用户）'],

            // 角色管理
            ['name' => 'roles.view', 'display_name' => '查看角色列表', 'group' => 'roles', 'description' => '查看角色列表'],
            ['name' => 'roles.create', 'display_name' => '创建角色', 'group' => 'roles', 'description' => '创建新角色'],
            ['name' => 'roles.edit', 'display_name' => '编辑角色', 'group' => 'roles', 'description' => '编辑角色信息'],
            ['name' => 'roles.delete', 'display_name' => '删除角色', 'group' => 'roles', 'description' => '删除角色'],
            ['name' => 'roles.assign_permissions', 'display_name' => '分配权限', 'group' => 'roles', 'description' => '给角色分配权限'],

            // 系统设置
            ['name' => 'settings.view', 'display_name' => '查看系统设置', 'group' => 'settings', 'description' => '查看系统设置'],
            ['name' => 'settings.edit', 'display_name' => '修改系统设置', 'group' => 'settings', 'description' => '修改系统设置'],

            // 元数据
            ['name' => 'metadata.search', 'display_name' => '搜索元数据', 'group' => 'metadata', 'description' => '搜索元数据（暂不适用）'],
            ['name' => 'metadata.fetch', 'display_name' => '获取元数据', 'group' => 'metadata', 'description' => '获取元数据详情（暂不适用）'],
            ['name' => 'metadata.batch_scrape', 'display_name' => '批量元数据刮削', 'group' => 'metadata', 'description' => '创建和管理批量元数据刮削任务'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                array_merge($permission, ['is_system' => true])
            );
        }

        echo "权限初始化完成!共创建 " . count($permissions) . " 个权限\n";
    }
}
