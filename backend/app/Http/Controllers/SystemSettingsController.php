<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Support\ApiHelpers;

class SystemSettingsController extends Controller
{
    // 仅管理员可访问：路由层使用 ['auth:sanctum','admin'] 中间件

    public function get(Request $request)
    {
        $values = SystemSetting::getAll();
        $categories = SystemSetting::categories();
        
        // 为每个分类的设置项处理 size 类型的 default_bytes
        foreach ($categories as &$category) {
            if (isset($category['items'])) {
                foreach ($category['items'] as $k => &$def) {
                    if (($def['type'] ?? 'string') === 'size') {
                        $def['default_bytes'] = SystemSetting::parseSizeToBytes($def['default'] ?? null);
                    }
                }
            }
        }
        
        return ApiHelpers::success(['values' => $values, 'categories' => $categories], '', 200);
    }

    public function update(Request $request)
    {
        // 支持两种请求体：{ data: { ... } } 或直接 { ... }
        $payload = $request->input('data');
        if ($payload === null) {
            // 允许直接把 JSON 对象作为请求体
            $payload = $request->json()->all();
        }
        if (!is_array($payload)) {
            return ApiHelpers::error('Invalid payload, expected object of settings', 422);
        }
        $values = SystemSetting::updateAll($payload);
        $categories = SystemSetting::categories();

        return ApiHelpers::success(['values' => $values, 'categories' => $categories], '', 200);
    }

    // 重置所有设置为默认值
    public function reset(Request $request)
    {
        $categories = SystemSetting::categories();
        $payload = [];

        // 遍历所有分类中的设置项，使用默认值
        foreach ($categories as $category) {
            if (!isset($category['items']) || !is_array($category['items'])) {
                continue;
            }
            foreach ($category['items'] as $key => $def) {
                $type = $def['type'] ?? 'string';
                $default = $def['default'] ?? null;
                $payload[$key] = ['type' => $type, 'value' => $default];
            }
        }

        // 删除数据库中所有设置，然后重新创建默认值
        SystemSetting::truncate();
        $values = SystemSetting::updateAll($payload);
        
        return ApiHelpers::success(['values' => $values, 'categories' => $categories], '所有设置已重置为默认值', 200);
    }

    // 公开：仅返回前端路由守卫所需的权限类配置和系统基本信息
    public function public(Request $request)
    {
        $all = SystemSetting::getAll();
        $pick = function(string $key, $default) use ($all) {
            return array_key_exists($key, $all) ? $all[$key] : $default;
        };
        return ApiHelpers::success([
            'system_name' => (string) $pick('system.system_name', 'Bard Library'),
            'permissions' => [
                'allow_guest_access' => (bool) $pick('permissions.allow_guest_access', true),
                'allow_user_registration' => (bool) $pick('permissions.allow_user_registration', true),
                'allow_recover_password' => (bool) $pick('permissions.allow_recover_password', true),
            ],
            'ui' => [
                'placeholder_cover' => (bool) $pick('ui.book.placeholder_cover', true),
            ],
        ], '', 200);
    }
}
