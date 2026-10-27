<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_PURCHASED = 'purchased';

    protected $fillable = [
        'user_id',
        'book_id',
        'status',
        'idempotency_key',
    ];

    protected $hidden = [
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'book_id' => 'integer',
        ];
    }
}
