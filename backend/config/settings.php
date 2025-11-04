<?php

return [
    // 分类结构：每个分类包含 label（显示名称）、icon（图标）、items（该分类下的设置项）
    'categories' => [
        'ui' => [
            'label' => 'UI设置',
            'icon' => 'palette',
            'items' => [
                'ui.book.placeholder_cover' => [
                    'type' => 'bool',
                    'default' => true,
                    'label' => '启用占位封面',
                    'description' => '当没有上传封面文件时，按标题/作者渲染占位封面',
                ],
            ],
        ],
        'permissions' => [
            'label' => '权限设置',
            'icon' => 'security',
            'items' => [
                'permissions.allow_guest_access' => [
                    'type' => 'bool',
                    'default' => true,
                    'label' => '允许游客访问',
                    'description' => '是否允许未登录用户访问，关闭后所有系统功能均需登录才能访问',
                ],
                'permissions.allow_user_registration' => [
                    'type' => 'bool',
                    'default' => true,
                    'label' => '允许新用户注册',
                    'description' => '是否允许新用户自行注册账号',
                ],
                'permissions.allow_recover_password' => [
                    'type' => 'bool',
                    'default' => true,
                    'label' => '允许找回密码',
                    'description' => '是否允许用户通过邮箱找回密码',
                ],
            ],
        ],
        'book' => [
            'label' => '图书设置',
            'icon' => 'book',
            'items' => [
                'book.upload_max_size' => [
                    'type' => 'size',
                    'default' => '100MB',
                    'label' => '上传大小限制',
                    'description' => '单个文件最大上传大小，支持如 100MB、1GB 的格式',
                ],
                'book.upload_max_batch' => [
                    'type' => 'int',
                    'default' => 10,
                    'label' => '批量上传文件数上限',
                    'description' => '单次批量上传允许的最大文件数',
                ],
            ],
        ],
    ],
];
