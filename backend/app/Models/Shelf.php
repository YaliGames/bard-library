<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shelf extends Model
{
    protected $fillable = ['name','description','user_id','is_public'];
    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_shelf');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
