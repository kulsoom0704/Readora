<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Book::create([
            'title' => 'Harry Potter',
            'author' => 'J.K. Rowling',
            'description' => 'A young wizard discovers his destiny.',
            'category_id' => 1,
        ]);

        Book::create([
            'title' => 'The Hobbit',
            'author' => 'J.R.R. Tolkien',
            'description' => 'A hobbit goes on an unexpected adventure.',
            'category_id' => 1,
        ]);

        Book::create([
            'title' => 'Pride and Prejudice',
            'author' => 'Jane Austen',
            'description' => 'A classic romance novel.',
            'category_id' => 2,
        ]);

        Book::create([
            'title' => 'Dune',
            'author' => 'Frank Herbert',
            'description' => 'A science fiction masterpiece.',
            'category_id' => 3,
        ]);
    }
}
