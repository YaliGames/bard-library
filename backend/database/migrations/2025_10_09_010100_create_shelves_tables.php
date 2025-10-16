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
            $table->timestamps();
            $table->unique(['name']);
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
