<?php

return [
    // 分类结构：每个分类包含 label（显示名称）、icon（图标）、items（该分类下的设置项）
    'categories' => [
        'system' => [
            'label' => '系统设置',
            'icon' => 'settings',
            'items' => [
                'system.system_name' => [
                    'type' => 'string',
                    'default' => 'Bard Library',
                    'label' => '系统名称',
                    'description' => '全局系统名称，显示在系统标题栏、主页等系统页面中，账号激活、找回密码等邮件均使用该名称',
                ],
                'system.admin_email' => [ // 未生效
                    'type' => 'string',
                    'default' => '',
                    'label' => '管理员邮箱',
                    'description' => '系统管理员联系邮箱，用于接收系统通知和用户反馈',
                ],
                'system.maintenance_mode' => [ // 未生效
                    'type' => 'bool',
                    'default' => false,
                    'label' => '维护模式',
                    'description' => '开启后，普通用户无法访问系统，仅超级管理员可以登录',
                ],
                'system.timezone' => [ // 未生效
                    'type' => 'string',
                    'default' => 'Asia/Shanghai',
                    'label' => '时区设置',
                    'description' => '系统默认时区，影响时间显示和日志记录',
                ],
                'system.language' => [ // 未生效
                    'type' => 'string',
                    'default' => 'zh-CN',
                    'label' => '默认语言',
                    'description' => '系统默认界面语言',
                ],
            ],
        ],
        'ui' => [
            'label' => '页面设置',
            'icon' => 'palette',
            'items' => [
                'ui.book.placeholder_cover' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '启用占位封面',
                    'description' => '当没有上传封面文件时，按标题/作者渲染占位封面',
                ],
                'ui.items_per_page' => [ // 未生效
                    'type' => 'int',
                    'default' => 20,
                    'label' => '每页显示数量',
                    'description' => '列表页面默认每页显示的项目数量',
                ],
                'ui.enable_dark_mode' => [ // 未生效
                    'type' => 'bool',
                    'default' => false,
                    'label' => '启用深色模式',
                    'description' => '是否允许用户切换到深色主题',
                ],
                'ui.show_reading_progress' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '显示阅读进度',
                    'description' => '在图书卡片上显示阅读进度条',
                ],
                'ui.enable_book_preview' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '启用图书预览',
                    'description' => '鼠标悬停时显示图书详细信息预览',
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
                'permissions.require_email_verification' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '强制邮箱验证',
                    'description' => '新用户注册后必须验证邮箱才能登录',
                ],
            ],
        ],
        'book' => [
            'label' => '图书设置',
            'icon' => 'book',
            'items' => [
                'book.allow_txt_upload' => [  // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '允许上传TXT文件',
                    'description' => '是否允许上传TXT格式的文件',
                ],
                'book.allow_epub_upload' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '允许上传EPUB文件',
                    'description' => '是否允许上传EPUB格式的文件',
                ],
                'book.allow_pdf_upload' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '允许上传PDF文件',
                    'description' => '是否允许上传PDF格式的文件',
                ],
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
                'book.enable_fulltext_search' => [ // 未生效
                    'type' => 'bool',
                    'default' => false,
                    'label' => '启用全文搜索',
                    'description' => '是否对图书内容建立全文索引（需要额外配置）',
                ],
            ],
        ],
        'notification' => [
            'label' => '通知设置',
            'icon' => 'notifications',
            'items' => [
                'notification.enable_email_notifications' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '启用邮件通知',
                    'description' => '是否发送系统邮件通知',
                ],
                'notification.notify_new_book' => [ // 未生效
                    'type' => 'bool',
                    'default' => false,
                    'label' => '新书通知',
                    'description' => '有新书上传时通知管理员',
                ],
                'notification.notify_new_user' => [ // 未生效
                    'type' => 'bool',
                    'default' => false,
                    'label' => '新用户通知',
                    'description' => '有新用户注册时通知管理员',
                ],
                'notification.notify_system_errors' => [ // 未生效
                    'type' => 'bool',
                    'default' => true,
                    'label' => '系统错误通知',
                    'description' => '系统发生错误时通知管理员',
                ],
            ],
        ],
        'security' => [
            'label' => '安全设置',
            'icon' => 'shield',
            'items' => [
                'security.password_min_length' => [ // 未生效
                    'type' => 'int',
                    'default' => 6,
                    'label' => '密码最小长度',
                    'description' => '用户密码的最小长度要求',
                ],
                'security.require_strong_password' => [ // 未生效
                    'type' => 'bool',
                    'default' => false,
                    'label' => '强制强密码',
                    'description' => '密码必须包含大小写字母、数字和特殊字符',
                ],
                'security.session_timeout' => [ // 未生效
                    'type' => 'int',
                    'default' => 120,
                    'label' => '会话超时时间',
                    'description' => '用户无操作后自动登出的时间（分钟）',
                ],
                'security.max_login_attempts' => [ // 未生效
                    'type' => 'int',
                    'default' => 5,
                    'label' => '最大登录尝试次数',
                    'description' => '连续登录失败多少次后锁定账户',
                ],
                'security.lockout_duration' => [ // 未生效
                    'type' => 'int',
                    'default' => 15,
                    'label' => '账户锁定时长',
                    'description' => '账户被锁定后的持续时间（分钟）',
                ],
            ],
        ],
    ],
];
