<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('reading_progress') && !Schema::hasTable('user_reading_progress')) {
            Schema::rename('reading_progress', 'user_reading_progress');
        }
        if (Schema::hasTable('bookmarks') && !Schema::hasTable('user_bookmarks')) {
            Schema::rename('bookmarks', 'user_bookmarks');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_reading_progress') && !Schema::hasTable('reading_progress')) {
            Schema::rename('user_reading_progress', 'reading_progress');
        }
        if (Schema::hasTable('user_bookmarks') && !Schema::hasTable('bookmarks')) {
            Schema::rename('user_bookmarks', 'bookmarks');
        }
    }
};
