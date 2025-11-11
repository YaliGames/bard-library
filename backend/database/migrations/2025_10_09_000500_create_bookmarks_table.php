<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('file_id')->nullable();
            $table->string('location'); // cfi/页码/章节标识
            $table->text('note')->nullable();
            $table->string('color', 16)->nullable()->comment('书签颜色');
            $table->timestamps();
            $table->index(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bookmarks');
    }
};
