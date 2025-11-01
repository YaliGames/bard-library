<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->string('name', 190);
            $table->string('description', 500)->nullable();
            // 所属用户：为空表示系统/管理员级（也允许管理员为自己创建）
            $table->unsignedBigInteger('user_id')->nullable()->index();
            // 是否公开：公开的书架对所有人可见（通常由管理员创建/管理）
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
            // 每个用户下名称唯一；对于 user_id 为空的系统级，同样按名称唯一
            $table->unique(['user_id','name']);
        });

        Schema::create('book_shelf', function (Blueprint $table) {
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('shelf_id');
            $table->primary(['book_id','shelf_id']);
            $table->index(['shelf_id','book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_shelf');
        Schema::dropIfExists('shelves');
    }
};
