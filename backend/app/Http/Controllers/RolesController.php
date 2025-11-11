<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class RolesController extends Controller
{
    /**
     * 获取角色列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Role::with('permissions');
        
        // 搜索
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        
        // 排序
        $sortBy = $request->get('sort', 'priority');
        $order = $request->get('order', 'desc');
        $query->orderBy($sortBy, $order);
        
        $roles = $query->get();
        
        // 添加用户数量
        $roles->each(function ($role) {
            $role->users_count = $role->users()->count();
        });
        
        return response()->json($roles);
    }

    /**
     * 获取单个角色详情
     */
    public function show(int $id): JsonResponse
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);
        $role->users_count = $role->users->count();
        
        return response()->json($role);
    }

    /**
     * 创建角色
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:roles|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'priority' => 'integer|min:0|max:999',
        ]);
        
        // 检查优先级不能超过当前用户的最高优先级
        $currentUserPriority = $request->user()->getHighestPriority();
        if (($validated['priority'] ?? 0) > $currentUserPriority) {
            return response()->json([
                'message' => 'Cannot create role with higher priority than yours'
            ], 403);
        }
        
        $role = Role::create($validated);
        
        return response()->json($role, 201);
    }

    /**
     * 更新角色
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        
        // 系统角色只有超级管理员可以修改
        if ($role->is_system && !$request->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot modify system role'], 403);
        }
        
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50', 'regex:/^[a-z_]+$/', Rule::unique('roles')->ignore($id)],
            'display_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'priority' => 'sometimes|integer|min:0|max:999',
        ]);
        
        // 检查优先级
        $currentUserPriority = $request->user()->getHighestPriority();
        if (isset($validated['priority']) && $validated['priority'] > $currentUserPriority) {
            return response()->json([
                'message' => 'Cannot set priority higher than yours'
            ], 403);
        }
        
        $role->update($validated);
        
        return response()->json($role);
    }

    /**
     * 删除角色
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        
        // 系统角色不能删除
        if ($role->is_system) {
            return response()->json(['message' => 'Cannot delete system role'], 403);
        }
        
        // 不能删除优先级更高的角色
        $currentUserPriority = $request->user()->getHighestPriority();
        if ($role->priority > $currentUserPriority) {
            return response()->json([
                'message' => 'Cannot delete role with higher priority than yours'
            ], 403);
        }
        
        // 检查是否有用户使用此角色
        if ($role->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete role that is assigned to users'
            ], 400);
        }
        
        $role->delete();
        
        return response()->json(['message' => 'Role deleted successfully']);
    }

    /**
     * 同步角色的权限
     */
    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        
        // 系统角色只有超级管理员可以修改
        if ($role->is_system && !$request->user()->hasRole('super_admin')) {
            return response()->json(['message' => 'Cannot modify system role permissions'], 403);
        }
        
        $validated = $request->validate([
            'permission_ids' => 'required|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);
        
        $role->permissions()->sync($validated['permission_ids']);
        
        return response()->json([
            'message' => 'Permissions synced successfully',
            'role' => $role->load('permissions')
        ]);
    }

    /**
     * 获取角色的权限列表
     */
    public function permissions(int $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);
        
        return response()->json($role->permissions);
    }
}
