<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadingProgress extends Model
{
    protected $table = 'user_reading_progress';
    protected $fillable = [
        'user_id','book_id','file_id','progress','location'
    ];
    protected $casts = [
        'progress' => 'float',
    ];
}
