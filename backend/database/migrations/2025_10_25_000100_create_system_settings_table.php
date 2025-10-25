<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            // key/value 存储，每一行为一项设置
            $table->string('key')->unique();
            // 类型：bool|int|string|json
            $table->string('type', 20)->default('string');
            // 值按类型以文本形式存储；json 类型为 JSON 文本
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
