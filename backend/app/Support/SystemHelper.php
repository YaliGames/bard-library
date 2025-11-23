<?php

namespace App\Support;

use App\Models\SystemSetting;

class SystemHelper
{
    /**
     * 获取系统名称
     */
    public static function getSystemName(): string
    {
        $settings = SystemSetting::getAll();
        return $settings['system.system_name'] ?? config('settings.categories.system.items.system.system_name.default', 'Bard Library');
    }

    /**
     * 获取所有公开的系统设置（不需要登录就能访问的设置）
     */
    public static function getPublicSettings(): array
    {
        $settings = SystemSetting::getAll();
        
        return [
            'system_name' => $settings['system.system_name'] ?? 'Bard Library',
            'allow_guest_access' => $settings['permissions.allow_guest_access'] ?? true,
            'allow_user_registration' => $settings['permissions.allow_user_registration'] ?? true,
            'allow_recover_password' => $settings['permissions.allow_recover_password'] ?? true,
            'placeholder_cover' => $settings['ui.book.placeholder_cover'] ?? true,
        ];
    }
}
