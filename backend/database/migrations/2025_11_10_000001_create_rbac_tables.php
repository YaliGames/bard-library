<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 创建角色表
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('角色标识符');
            $table->string('display_name', 100)->comment('显示名称');
            $table->text('description')->nullable()->comment('角色描述');
            $table->boolean('is_system')->default(false)->comment('系统角色(不可删除)');
            $table->integer('priority')->default(0)->comment('优先级,数字越大权限越高');
            $table->timestamps();
            
            $table->index('priority');
        });

        // 创建权限表
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('权限标识符');
            $table->string('display_name', 100)->comment('显示名称');
            $table->string('group', 50)->comment('权限分组');
            $table->text('description')->nullable()->comment('权限描述');
            $table->boolean('is_system')->default(false)->comment('系统权限(不可删除)');
            $table->timestamps();
            
            $table->index('group');
        });

        // 创建用户-角色关联表
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'role_id']);
            $table->index('user_id');
            $table->index('role_id');
        });

        // 创建角色-权限关联表
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['role_id', 'permission_id']);
            $table->index('role_id');
            $table->index('permission_id');
        });

        // 初始化权限和角色数据
        $this->seedPermissionsAndRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }

    /**
     * 初始化权限和角色数据
     */
    private function seedPermissionsAndRoles(): void
    {
        // 调用权限种子数据
        (new \Database\Seeders\PermissionsSeeder())->run();
        
        // 调用角色种子数据
        (new \Database\Seeders\RolesSeeder())->run();
    }
};
