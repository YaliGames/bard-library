<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TxtChapter extends Model
{
    protected $table = 'txt_chapters';
    protected $fillable = ['file_id','index','title','offset','length'];
}
