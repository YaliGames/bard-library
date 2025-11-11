<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_read_marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('book_id');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->unique(['user_id','book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_read_marks');
    }
};
