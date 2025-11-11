<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    /**
     * 获取用户列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');
        
        // 搜索
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // 按角色筛选
        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }
        
        // 邮箱验证状态筛选
        if ($request->has('email_verified')) {
            if ($request->email_verified === 'true') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }
        
        // 排序
        $sortBy = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sortBy, $order);
        
        // 分页
        $perPage = $request->get('per_page', 20);
        $users = $query->paginate($perPage);
        
        return response()->json($users);
    }

    /**
     * 获取单个用户详情
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('roles.permissions')->findOrFail($id);
        
        return response()->json($user);
    }

    /**
     * 创建用户
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
        ]);
        
        // 创建用户
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        
        // 分配角色
        if (isset($validated['role_ids'])) {
            // 检查是否尝试分配更高优先级的角色
            $targetRoles = Role::whereIn('id', $validated['role_ids'])->get();
            $maxTargetPriority = $targetRoles->max('priority');
            $currentUserPriority = $request->user()->getHighestPriority();
            
            if ($maxTargetPriority > $currentUserPriority) {
                return response()->json([
                    'message' => 'Cannot assign roles with higher priority than yours'
                ], 403);
            }
            
            $user->roles()->sync($validated['role_ids']);
        } else {
            // 如果没有指定角色,分配默认角色
            $defaultRole = Role::where('name', 'user')->first();
            if ($defaultRole) {
                $user->roles()->attach($defaultRole->id);
            }
        }
        
        return response()->json($user->load('roles'), 201);
    }

    /**
     * 更新用户信息
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($id)],
            'password' => 'sometimes|string|min:8',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
        ]);
        
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        
        $user->update($validated);
        
        return response()->json($user);
    }

    /**
     * 删除用户
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // 不能删除自己
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot delete yourself'], 403);
        }
        
        // 不能删除优先级更高的用户
        $targetUserPriority = $user->getHighestPriority();
        $currentUserPriority = $request->user()->getHighestPriority();
        
        if ($targetUserPriority > $currentUserPriority) {
            return response()->json([
                'message' => 'Cannot delete user with higher privilege than yours'
            ], 403);
        }
        
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * 同步用户的角色
     */
    public function syncRoles(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // 不能修改自己的角色
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Cannot modify your own roles'], 403);
        }
        
        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ]);
        
        // 检查是否尝试分配更高优先级的角色
        $targetRoles = Role::whereIn('id', $validated['role_ids'])->get();
        $maxTargetPriority = $targetRoles->max('priority');
        $currentUserPriority = $request->user()->getHighestPriority();
        
        if ($maxTargetPriority > $currentUserPriority) {
            return response()->json([
                'message' => 'Cannot assign roles with higher priority than yours'
            ], 403);
        }
        
        $user->roles()->sync($validated['role_ids']);
        
        return response()->json([
            'message' => 'Roles synced successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * 获取用户的角色列表
     */
    public function roles(int $id): JsonResponse
    {
        $user = User::with('roles')->findOrFail($id);
        
        return response()->json($user->roles);
    }

    /**
     * 获取用户的所有权限
     */
    public function permissions(int $id): JsonResponse
    {
        $user = User::with('roles.permissions')->findOrFail($id);
        
        // 收集所有权限(去重)
        $permissions = $user->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id')->values();
        
        return response()->json($permissions);
    }
}
