<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'subtitle', 'description', 'rating', 'language', 'publisher', 'published_at',
        'isbn10', 'isbn13', 'series_id', 'series_index', 'cover_file_id', 'created_by', 'meta',
    ];

    protected $casts = [
        // ensure published_at serializes as date-only string (Y-m-d)
        'published_at' => 'date:Y-m-d',
        'rating' => 'integer',
        'meta' => 'array',
    ];

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'book_authors')->withPivot('role');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'book_tags');
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function shelves()
    {
        return $this->belongsToMany(Shelf::class, 'book_shelf');
    }
}
