<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('language', 16)->nullable();
            $table->date('publisher')->nullable();
            $table->date('published_at')->nullable();
            $table->string('isbn10', 10)->nullable()->unique();
            $table->string('isbn13', 13)->nullable()->unique();
            $table->unsignedBigInteger('series_id')->nullable();
            $table->unsignedBigInteger('series_index')->nullable();
            $table->unsignedBigInteger('cover_file_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('series_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
