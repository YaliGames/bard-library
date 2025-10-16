<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('location')->nullable()->after('email');
            $table->string('website')->nullable()->after('location');
            $table->text('bio')->nullable()->after('website');
            $table->timestamp('deletion_requested_at')->nullable()->after('email_verified_at');
            $table->string('deletion_reason')->nullable()->after('deletion_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['location','website','bio','deletion_requested_at','deletion_reason']);
        });
    }
};
