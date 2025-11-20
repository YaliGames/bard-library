<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapingResult extends Model
{
    protected $fillable = [
        'task_id',
        'provider',
        'source_id',
        'source_url',
        'query',
        'metadata',
        'status',
        'book_id',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ScrapingTask::class, 'task_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
