<?php

namespace App\Services;

use App\Models\Book;

class InventoryReadService
{
    public function __construct(private readonly InventoryApiClient $client) {}

    /**
     * Read verified stock from the Inventory API for one Readora book.
     *
     * @return array{state: 'available'|'out_of_stock'|'unknown'|'missing'|'unavailable', stock: int|null, available: bool}
     */
    public function forBook(int $bookId): array
    {
        try {
            $data = $this->client
                ->request('GET', '/api/v1/inventory/books/'.$bookId)
                ->json('data');
        } catch (InventoryApiException $exception) {
            if ($exception->statusCode === 404) {
                return $this->result('missing');
            }

            return $this->result('unavailable');
        }

        if (
            ! is_array($data)
            || ! isset($data['book_id'])
            || (int) $data['book_id'] !== $bookId
            || ! array_key_exists('stock', $data)
            || ! is_bool($data['available'] ?? null)
        ) {
            return $this->result('unavailable');
        }

        $stock = $data['stock'];

        if ($stock === null) {
            return $data['available'] ? $this->result('unavailable') : $this->result('unknown');
        }

        if (! is_int($stock) || $stock < 0 || $data['available'] !== ($stock > 0)) {
            return $this->result('unavailable');
        }

        return $this->result($stock > 0 ? 'available' : 'out_of_stock', $stock);
    }

    /**
     * Read API stock for a collection of books.
     *
     * @param  iterable<Book>  $books
     * @return array<int, array{state: 'available'|'out_of_stock'|'unknown'|'missing'|'unavailable', stock: int|null, available: bool}>
     */
    public function forBooks(iterable $books): array
    {
        $inventoryByBookId = [];

        foreach ($books as $book) {
            $inventoryByBookId[(int) $book->getKey()] = $this->forBook((int) $book->getKey());
        }

        return $inventoryByBookId;
    }

    /**
     * @param  'available'|'out_of_stock'|'unknown'|'missing'|'unavailable'  $state
     * @return array{state: 'available'|'out_of_stock'|'unknown'|'missing'|'unavailable', stock: int|null, available: bool}
     */
    private function result(string $state, ?int $stock = null): array
    {
        return [
            'state' => $state,
            'stock' => $stock,
            'available' => $state === 'available',
        ];
    }
}
