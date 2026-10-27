<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'book_id',
        'stock',
    ];

    protected function casts(): array
    {
        return [
            'book_id' => 'integer',
            'stock' => 'integer',
        ];
    }
}
