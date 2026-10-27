<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateInventoryRequest;
use App\Models\Inventory;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InventoryController extends Controller
{
    public function show(string $bookId): Response
    {
        $inventory = Inventory::where('book_id', $bookId)->firstOrFail();

        return response()->json(['data' => $this->inventoryData($inventory)]);
    }

    public function update(UpdateInventoryRequest $request, string $bookId): Response
    {
        $stock = $request->validated('stock');

        $result = DB::transaction(function () use ($bookId, $stock): array {
            if ($stock === null && Reservation::where('book_id', $bookId)->where('status', Reservation::STATUS_ACTIVE)->exists()) {
                return ['conflict' => true];
            }

            $inventory = Inventory::updateOrCreate(
                ['book_id' => (int) $bookId],
                ['stock' => $stock],
            );

            return ['inventory' => $inventory, 'conflict' => false];
        });

        if ($result['conflict']) {
            return response()->json([
                'message' => 'Stock cannot be marked unknown while active reservations exist.',
            ], Response::HTTP_CONFLICT);
        }

        return response()->json(['data' => $this->inventoryData($result['inventory'])]);
    }

    /** @return array{book_id: int, stock: int|null, available: bool} */
    private function inventoryData(Inventory $inventory): array
    {
        return [
            'book_id' => (int) $inventory->book_id,
            'stock' => $inventory->stock,
            'available' => $inventory->stock !== null && $inventory->stock > 0,
        ];
    }
}
