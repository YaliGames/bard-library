<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 定义角色
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => '超级管理员',
                'description' => '拥有所有权限的超级管理员',
                'is_system' => true,
                'priority' => 1000,
                'permissions' => ['*'], // 特殊标记,表示所有权限
            ],
            [
                'name' => 'admin',
                'display_name' => '管理员',
                'description' => '系统管理员，拥有大部分管理权限',
                'is_system' => true,
                'priority' => 900,
                'permissions' => [
                    'books.*',
                    'authors.*',
                    'tags.*',
                    'series.*',
                    'shelves.*',
                    'files.*',
                    'settings.view',
                    'metadata.*',
                ],
            ],
            [
                'name' => 'editor',
                'display_name' => '编辑',
                'description' => '内容编辑，可以管理图书、作者、标签等内容',
                'is_system' => true,
                'priority' => 500,
                'permissions' => [
                    'books.view',
                    'books.create',
                    'books.edit',
                    'books.upload',
                    'books.download',
                    'authors.view',
                    'authors.create',
                    'authors.edit',
                    'tags.view',
                    'tags.create',
                    'tags.edit',
                    'series.view',
                    'series.create',
                    'series.edit',
                    'shelves.view',
                    'shelves.create',
                    'shelves.edit',
                    'shelves.delete',
                    'shelves.create_public',
                    'files.view',
                    'files.upload',
                    'files.delete',
                    'metadata.search',
                    'metadata.fetch',
                ],
            ],
            [
                'name' => 'user',
                'display_name' => '用户',
                'description' => '普通用户，可以浏览、下载图书，管理自己的书架',
                'is_system' => true,
                'priority' => 100,
                'permissions' => [
                    'books.download',
                    'shelves.create',
                    'shelves.edit',
                    'shelves.delete',
                ],
            ],
            [
                'name' => 'guest',
                'display_name' => '访客',
                'description' => '访客用户，只能浏览基本信息',
                'is_system' => true,
                'priority' => 10,
                'permissions' => [
                    // 访客没有任何权限,只能浏览公开内容
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );

            // 分配权限
            if ($permissions === ['*']) {
                // 超级管理员:创建一个特殊的全局权限
                $globalPermission = Permission::firstOrCreate(
                    ['name' => '*'],
                    [
                        'display_name' => '全部权限',
                        'group' => 'system',
                        'description' => '拥有系统所有权限',
                        'is_system' => true,
                    ]
                );
                $role->permissions()->sync([$globalPermission->id]);
            } else {
                // 其他角色:根据权限名称匹配
                $permissionIds = [];
                foreach ($permissions as $permName) {
                    if (str_ends_with($permName, '.*')) {
                        // 通配符权限:匹配该分组的所有权限
                        $group = str_replace('.*', '', $permName);
                        $groupPermissions = Permission::where('group', $group)->pluck('id');
                        $permissionIds = array_merge($permissionIds, $groupPermissions->toArray());
                    } else {
                        // 精确权限
                        $permission = Permission::where('name', $permName)->first();
                        if ($permission) {
                            $permissionIds[] = $permission->id;
                        }
                    }
                }
                $role->permissions()->sync(array_unique($permissionIds));
            }

            echo "角色 [{$role->display_name}] 创建完成,已分配 " . count($permissionIds ?? [1]) . " 个权限\n";
        }

        echo "角色初始化完成!\n";
    }
}
