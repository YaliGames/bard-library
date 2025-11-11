<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionsController extends Controller
{
    /**
     * 获取所有权限列表
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();
        
        return response()->json($permissions);
    }

    /**
     * 获取按分组归类的权限
     */
    public function grouped(): JsonResponse
    {
        $grouped = Permission::getGrouped();
        
        return response()->json($grouped);
    }

    /**
     * 获取所有权限分组
     */
    public function groups(): JsonResponse
    {
        $groups = Permission::getGroups();
        
        return response()->json($groups);
    }
}
