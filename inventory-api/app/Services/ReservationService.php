<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Reservation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /** @return array{reservation: Reservation|null, created: bool, conflict: bool} */
    public function reserve(int $userId, int $bookId, string $idempotencyKey): array
    {
        try {
            return DB::transaction(function () use ($userId, $bookId, $idempotencyKey): array {
                $stockReduced = Inventory::where('book_id', $bookId)
                    ->whereNotNull('stock')
                    ->where('stock', '>', 0)
                    ->decrement('stock');

                if ($stockReduced !== 1) {
                    $existing = Reservation::where('user_id', $userId)
                        ->where('idempotency_key', $idempotencyKey)
                        ->first();

                    if ($existing !== null) {
                        return [
                            'reservation' => $existing,
                            'created' => false,
                            'conflict' => (int) $existing->book_id !== $bookId,
                        ];
                    }

                    return ['reservation' => null, 'created' => false, 'conflict' => true];
                }

                $reservation = Reservation::create([
                    'user_id' => $userId,
                    'book_id' => $bookId,
                    'status' => Reservation::STATUS_ACTIVE,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return ['reservation' => $reservation, 'created' => true, 'conflict' => false];
            }, attempts: 3);
        } catch (QueryException $exception) {
            $existing = Reservation::where('user_id', $userId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing === null) {
                $stockIsUnavailable = ! Inventory::where('book_id', $bookId)
                    ->whereNotNull('stock')
                    ->where('stock', '>', 0)
                    ->exists();

                if ($stockIsUnavailable) {
                    return ['reservation' => null, 'created' => false, 'conflict' => true];
                }

                throw $exception;
            }

            return [
                'reservation' => $existing,
                'created' => false,
                'conflict' => (int) $existing->book_id !== $bookId,
            ];
        }
    }

    /** @return array{status: string, reservation: Reservation|null} */
    public function cancel(int $userId, int $reservationId): array
    {
        return DB::transaction(function () use ($userId, $reservationId): array {
            $reservation = Reservation::whereKey($reservationId)
                ->where('user_id', $userId)
                ->first();

            if ($reservation === null) {
                return ['status' => 'missing', 'reservation' => null];
            }

            $inventory = Inventory::where('book_id', $reservation->book_id)->first();

            if ($inventory === null || $inventory->stock === null) {
                return ['status' => 'unknown_stock', 'reservation' => null];
            }

            $changed = Reservation::whereKey($reservationId)
                ->where('user_id', $userId)
                ->where('status', Reservation::STATUS_ACTIVE)
                ->update([
                    'status' => Reservation::STATUS_CANCELLED,
                    'updated_at' => now(),
                ]);

            if ($changed !== 1) {
                return ['status' => 'inactive', 'reservation' => null];
            }

            $stockRestored = Inventory::where('book_id', $reservation->book_id)
                ->whereNotNull('stock')
                ->increment('stock');

            if ($stockRestored !== 1) {
                throw new \RuntimeException('The reservation was not cancelled because its stock could not be restored.');
            }

            return [
                'status' => 'cancelled',
                'reservation' => $reservation->fresh(),
            ];
        }, attempts: 3);
    }

    /** @return array{status: string, reservation: Reservation|null} */
    public function purchase(int $reservationId): array
    {
        return DB::transaction(function () use ($reservationId): array {
            $changed = Reservation::whereKey($reservationId)
                ->where('status', Reservation::STATUS_ACTIVE)
                ->update([
                    'status' => Reservation::STATUS_PURCHASED,
                    'updated_at' => now(),
                ]);

            if ($changed !== 1) {
                return ['status' => 'inactive', 'reservation' => null];
            }

            return [
                'status' => 'purchased',
                'reservation' => Reservation::findOrFail($reservationId),
            ];
        }, attempts: 3);
    }
}
