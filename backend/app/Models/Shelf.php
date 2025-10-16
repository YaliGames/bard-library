<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shelf extends Model
{
    protected $fillable = ['name','description'];

    public function books()
    {
        return $this->belongsToMany(Book::class, 'book_shelf');
    }
}
