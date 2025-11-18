<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\SystemSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (!\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        // 确保系统设置的默认值存在
        $categories = SystemSetting::categories();
        if (!empty($categories)) {
            // 遍历所有分类中的设置项
            foreach ($categories as $category) {
                if (!isset($category['items']) || !is_array($category['items'])) {
                    continue;
                }
                foreach ($category['items'] as $key => $def) {
                    // 仅在缺失时填充默认值，不覆盖已有设置
                    if (\App\Models\SystemSetting::where('key', $key)->exists()) continue;
                    $type = $def['type'] ?? 'string';
                    $default = $def['default'] ?? null;
                    // 对 size 类型的默认值转换为字节后存储
                    if ($type === 'size') {
                        $bytes = SystemSetting::parseSizeToBytes($default);
                        $valueToStore = (string) ($bytes ?? 0);
                    } elseif ($type === 'json') {
                        $valueToStore = json_encode($default, JSON_UNESCAPED_UNICODE);
                    } elseif ($type === 'bool') {
                        $valueToStore = $default ? '1' : '0';
                    } elseif ($type === 'int') {
                        $valueToStore = (string) intval($default);
                    } else {
                        $valueToStore = (string) $default;
                    }
                    SystemSetting::firstOrCreate(
                        ['key' => $key],
                        ['type' => $type, 'value' => $valueToStore]
                    );
                }
            }
        }
    }
}
