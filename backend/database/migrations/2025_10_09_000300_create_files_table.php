<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->string('format', 16);
            $table->unsignedBigInteger('size');
            $table->string('mime', 64)->nullable();
            $table->string('sha256', 64)->unique();
            $table->text('path');
            $table->string('storage', 32)->default('library');
            $table->integer('pages')->nullable();
            $table->timestamps();
            $table->index('book_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
