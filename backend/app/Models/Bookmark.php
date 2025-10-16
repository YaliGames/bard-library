<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $table = 'user_bookmarks';
    protected $fillable = [
        'user_id','book_id','file_id','location','note','color'
    ];
}
