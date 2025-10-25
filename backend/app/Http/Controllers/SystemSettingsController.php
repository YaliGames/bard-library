<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemSetting;

class SystemSettingsController extends Controller
{
    // 仅管理员可访问：路由层使用 ['auth:sanctum','admin'] 中间件

    public function get(Request $request)
    {
        $data = SystemSetting::getAll();
        return response()->json($data);
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
        $data = SystemSetting::updateAll($payload);
        return response()->json($data);
    }
}
