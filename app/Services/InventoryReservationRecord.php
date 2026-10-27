<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Read-only reservation data returned by the Inventory API, joined to local catalogue and user records.
 */
final readonly class InventoryReservationRecord
{
    public function __construct(
        public int $id,
        public int $user_id,
        public int $book_id,
        public string $status,
        public Carbon $created_at,
        public Carbon $updated_at,
        public ?Book $book,
        public ?User $user,
    ) {}
}
