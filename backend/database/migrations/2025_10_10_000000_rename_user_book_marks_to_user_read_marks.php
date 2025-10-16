<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_book_marks') && !Schema::hasTable('user_read_marks')) {
            Schema::rename('user_book_marks', 'user_read_marks');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_read_marks') && !Schema::hasTable('user_book_marks')) {
            Schema::rename('user_read_marks', 'user_book_marks');
        }
    }
};
