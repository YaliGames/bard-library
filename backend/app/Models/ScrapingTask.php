<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapingTask extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'status',
        'total_items',
        'processed_items',
        'success_items',
        'failed_items',
        'options',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'options' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ScrapingResult::class, 'task_id');
    }

    public function updateProgress(): void
    {
        $this->processed_items = $this->results()->whereIn('status', ['success', 'failed', 'skipped'])->count();
        $this->success_items = $this->results()->where('status', 'success')->count();
        $this->failed_items = $this->results()->where('status', 'failed')->count();
        $this->save();
    }
}
