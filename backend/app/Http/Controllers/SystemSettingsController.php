<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;

class SystemSettingsController extends Controller
{
    // 仅管理员可访问：路由层使用 ['auth:sanctum','admin'] 中间件

    public function get(Request $request)
    {
        $values = SystemSetting::getAll();
        $schema = SystemSetting::schema();
        foreach ($schema as $k => &$def) {
            if (($def['type'] ?? 'string') === 'size') {
                $def['default_bytes'] = SystemSetting::parseSizeToBytes($def['default'] ?? null);
            }
        }
        return response()->json([ 'values' => $values, 'schema' => $schema ]);
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
            return response()->json(['message' => 'Invalid payload, expected object of settings'], 422);
        }
        $values = SystemSetting::updateAll($payload);
        return response()->json([ 'values' => $values, 'schema' => SystemSetting::schema() ]);
    }
}
