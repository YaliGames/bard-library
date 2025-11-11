<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
        'priority',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'priority' => 'integer',
    ];

    protected $appends = ['users_count'];

    /**
     * 角色拥有的用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * 角色拥有的权限
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * 检查角色是否有某个权限
     * 支持通配符匹配: books.* 匹配 books.create, books.edit 等
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains(function ($p) use ($permission) {
            // 全局权限
            if ($p->name === '*') {
                return true;
            }
            
            // 精确匹配
            if ($p->name === $permission) {
                return true;
            }
            
            // 通配符匹配: books.* 匹配 books.create
            if (str_contains($p->name, '*')) {
                $pattern = str_replace('*', '.*', preg_quote($p->name, '/'));
                return (bool) preg_match("/^{$pattern}$/", $permission);
            }
            
            return false;
        });
    }

    /**
     * 给角色分配权限
     */
    public function givePermissionTo(string|array|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }
        
        if (is_array($permission)) {
            $permissionIds = Permission::whereIn('name', $permission)->pluck('id');
            $this->permissions()->syncWithoutDetaching($permissionIds);
            return;
        }
        
        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * 撤销角色的权限
     */
    public function revokePermissionTo(string|array|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
            if (!$permission) return;
        }
        
        if (is_array($permission)) {
            $permissionIds = Permission::whereIn('name', $permission)->pluck('id');
            $this->permissions()->detach($permissionIds);
            return;
        }
        
        $this->permissions()->detach($permission->id);
    }

    /**
     * 同步角色的权限
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->sync($permissionIds);
    }

    /**
     * 获取用户数量
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }
}
