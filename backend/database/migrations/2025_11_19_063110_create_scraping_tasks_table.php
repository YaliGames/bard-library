<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scraping_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, cancelled
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('success_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->json('options')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });

        Schema::create('scraping_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('scraping_tasks')->onDelete('cascade');
            $table->string('provider'); // douban, jjwxc
            $table->string('source_id');
            $table->string('source_url');
            $table->string('query');
            $table->json('metadata');
            $table->string('status')->default('pending'); // pending, success, failed, skipped
            $table->foreignId('book_id')->nullable()->constrained()->onDelete('set null');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['task_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraping_results');
        Schema::dropIfExists('scraping_tasks');
    }
};
