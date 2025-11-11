<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'group',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * 权限所属的角色
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')
            ->withTimestamps();
    }

    /**
     * 获取按分组归类的权限
     */
    public static function getGrouped(): Collection
    {
        return static::all()
            ->groupBy('group')
            ->map(fn($items) => $items->sortBy('name')->values());
    }

    /**
     * 获取所有权限分组
     */
    public static function getGroups(): array
    {
        return static::distinct('group')
            ->orderBy('group')
            ->pluck('group')
            ->toArray();
    }
}
