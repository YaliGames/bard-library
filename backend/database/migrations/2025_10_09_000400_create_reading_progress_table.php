<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_reading_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('file_id')->nullable();
            $table->decimal('progress', 5, 4)->default(0); // 0.0000 ~ 1.0000
            $table->string('location')->nullable(); // 例如 epub cfi / 页码 / 章节标识
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
            $table->index(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reading_progress');
    }
};
