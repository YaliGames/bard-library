<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('txt_chapters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('file_id'); // 关联 TXT 文件记录
            $table->unsignedInteger('index')->comment('章节序号，从 0 开始');
            $table->string('title', 255)->nullable();
            $table->unsignedBigInteger('offset')->comment('起始字节偏移');
            $table->unsignedBigInteger('length')->comment('字节长度');
            $table->timestamps();
            $table->unique(['file_id','index']);
            $table->index(['file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('txt_chapters');
    }
};
