<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'subtitle' => $this->faker->optional()->sentence(5),
            'description' => $this->faker->optional()->paragraph(),
            'language' => $this->faker->randomElement(['zh','en','ja']),
            'publisher' => $this->faker->optional()->company(),
            'published_at' => $this->faker->optional()->date(),
            'isbn10' => null,
            'isbn13' => null,
            'series_id' => null,
            'series_index' => null,
            'cover_file_id' => null,
            'created_by' => null,
            'meta' => null,
        ];
    }
}
