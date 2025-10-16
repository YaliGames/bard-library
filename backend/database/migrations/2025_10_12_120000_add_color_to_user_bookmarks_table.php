<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_bookmarks') && !Schema::hasColumn('user_bookmarks', 'color')) {
            Schema::table('user_bookmarks', function (Blueprint $table) {
                $table->string('color', 16)->nullable()->after('note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_bookmarks') && Schema::hasColumn('user_bookmarks', 'color')) {
            Schema::table('user_bookmarks', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }
    }
};
