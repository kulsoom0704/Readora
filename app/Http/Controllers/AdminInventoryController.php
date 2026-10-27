<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\InventoryApiClient;
use App\Services\InventoryApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminInventoryController extends Controller
{
    public function update(Request $request, Book $book, InventoryApiClient $inventoryApi): RedirectResponse
    {
        $stockField = 'stock.'.$book->getKey();
        $validated = $request->validate([
            $stockField => ['required', 'integer', 'min:0'],
        ]);
        $stock = (int) data_get($validated, $stockField);

        try {
            $response = $inventoryApi->request(
                'PUT',
                '/api/v1/inventory/books/'.$book->getKey(),
                ['stock' => $stock],
                $request->user(),
            );
        } catch (InventoryApiException $exception) {
            $message = $exception->statusCode === null
                ? 'The Inventory API is unavailable or timed out. Stock was not confirmed as updated.'
                : 'The Inventory API rejected the stock update (HTTP '.$exception->statusCode.'). The update was not confirmed.';

            return redirect()
                ->route('admin.books.index')
                ->withInput()
                ->withErrors([$stockField => $message]);
        }

        $data = $response->json('data');

        if (
            ! is_array($data)
            || (int) ($data['book_id'] ?? 0) !== (int) $book->getKey()
            || ! is_int($data['stock'] ?? null)
            || $data['stock'] !== $stock
            || ($data['available'] ?? null) !== ($stock > 0)
        ) {
            return redirect()
                ->route('admin.books.index')
                ->withInput()
                ->withErrors([
                    $stockField => 'The Inventory API response could not confirm this stock update.',
                ]);
        }

        return redirect()
            ->route('admin.books.index')
            ->with('success', 'Inventory updated successfully.');
    }
}
