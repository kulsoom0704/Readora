<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder

{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Fantasy',
            'Romance',
            'Science Fiction',
            'Mystery',
            'Horror',
            'History',
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category
            ]);
        }
    }
    }
