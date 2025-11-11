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
        // 扩展 system_settings 表
        Schema::table('system_settings', function (Blueprint $table) {
            $table->foreignId('default_role_id')->nullable()->constrained('roles')->comment('默认注册用户角色');
            $table->boolean('require_email_verification')->default(true)->comment('是否需要邮箱验证');
            $table->boolean('allow_self_registration')->default(true)->comment('是否允许自助注册');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropForeign(['default_role_id']);
            $table->dropColumn(['default_role_id', 'require_email_verification', 'allow_self_registration']);
        });
    }
};
