<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'location',
        'website',
        'bio',
        'deletion_requested_at',
        'deletion_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'deletion_requested_at' => 'datetime',
        ];
    }

    /**
     * 用户拥有的角色
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * 检查用户是否有某个角色
     */
    public function hasRole(string|array $role): bool
    {
        if (is_array($role)) {
            return $this->roles()->whereIn('name', $role)->exists();
        }
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * 检查用户是否有某个权限
     */
    public function hasPermission(string $permission): bool
    {
        // 检查用户的所有角色是否有该权限
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 重写 can 方法以使用 $user->can('permission') 
     */
    public function can($abilities, $arguments = []): bool
    {
        // 如果是数组，检查是否有任一权限
        if (is_array($abilities)) {
            return $this->hasAnyPermission($abilities);
        }
        
        // 单个权限检查
        return $this->hasPermission($abilities);
    }

    /**
     * 检查用户是否有任一权限
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查用户是否有所有权限
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取用户最高的角色优先级
     */
    public function getHighestPriority(): int
    {
        return $this->roles->max('priority') ?? 0;
    }

    /**
     * 给用户分配角色
     */
    public function assignRole(string|array|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }
        
        if (is_array($role)) {
            $roleIds = Role::whereIn('name', $role)->pluck('id');
            $this->roles()->syncWithoutDetaching($roleIds);
            return;
        }
        
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * 移除用户的角色
     */
    public function removeRole(string|array|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
            if (!$role) return;
        }
        
        if (is_array($role)) {
            $roleIds = Role::whereIn('name', $role)->pluck('id');
            $this->roles()->detach($roleIds);
            return;
        }
        
        $this->roles()->detach($role->id);
    }

    /**
     * 同步用户的角色
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = Role::whereIn('name', $roles)->pluck('id');
        $this->roles()->sync($roleIds);
    }
}
