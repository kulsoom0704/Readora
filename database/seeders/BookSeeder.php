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
            'description' => 'Harry Potter is a young wizard who discovers his magical powers and begins studying at Hogwarts School of Witchcraft and Wizardry, where he faces friendship, adventure, and dark enemies.',
            'category_id' => 1,
        ]);

        Book::create([
            'title' => 'The Hobbit',
            'author' => 'J.R.R. Tolkien',
            'description' => 'The Hobbit follows Bilbo Baggins, a quiet hobbit who is unexpectedly taken on a dangerous adventure with a group of dwarves to reclaim their homeland and treasure from the dragon Smaug.',
            'category_id' => 1,
        ]);

        Book::create([
            'title' => 'Pride and Prejudice',
            'author' => 'Jane Austen',
            'description' => 'Pride and Prejudice is a romantic novel that critiques the British landed gentry at the end of the 18th century.',
            'category_id' => 2,
        ]);

        Book::create([
            'title' => 'Dune',
            'author' => 'Frank Herbert',
            'description' => 'Dune follows Paul Atreides as he navigates politics, war, and destiny on the dangerous desert planet Arrakis, the only source of the valuable spice melange.',
            'category_id' => 3,
        ]);
    }
}
