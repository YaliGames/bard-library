<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 190);
            $table->string('sort_name', 190)->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('type', 32)->nullable();
            $table->timestamps();
            $table->unique(['name','type']);
        });

        Schema::create('book_authors', function (Blueprint $table) {
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('author_id');
            $table->string('role', 32)->default('author');
            $table->primary(['book_id','author_id','role']);
        });

        Schema::create('book_tags', function (Blueprint $table) {
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['book_id','tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_tags');
        Schema::dropIfExists('book_authors');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('authors');
    }
};
