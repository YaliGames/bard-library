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
        // 确保系统设置的默认值存在
        $schema = SystemSetting::schema();
        if (!empty($schema)) {
            foreach ($schema as $key => $def) {
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
