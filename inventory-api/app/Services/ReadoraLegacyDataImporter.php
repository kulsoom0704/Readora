<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class ReadoraLegacyDataImporter
{
    /**
     * Validate, inspect and optionally import a Readora export without changing conflicting API data.
     *
     * @param  array<string, mixed>  $payload
     * @return array{dry_run: bool, inventories_inserted: int, inventories_unchanged: int, reservations_inserted: int, reservations_unchanged: int}
     */
    public function import(array $payload, bool $dryRun = true): array
    {
        $data = $this->validatePayload($payload);
        $this->validateReferences($data);

        return DB::transaction(function () use ($data, $dryRun): array {
            $plan = $this->buildPlan($data);

            if ($plan['conflicts'] !== []) {
                throw new RuntimeException('Legacy import conflicts: '.implode('; ', $plan['conflicts']).'. No records were written.');
            }

            if (! $dryRun) {
                foreach ($plan['new_inventories'] as $inventory) {
                    DB::table('inventories')->insert([
                        'book_id' => $inventory['book_id'],
                        'stock' => $inventory['stock'],
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]);
                }

                foreach ($plan['new_reservations'] as $reservation) {
                    DB::table('reservations')->insert($reservation);
                }
            }

            return [
                'dry_run' => $dryRun,
                'inventories_inserted' => count($plan['new_inventories']),
                'inventories_unchanged' => $plan['inventories_unchanged'],
                'reservations_inserted' => count($plan['new_reservations']),
                'reservations_unchanged' => $plan['reservations_unchanged'],
            ];
        }, attempts: 3);
    }

    /** @param array<string, mixed> $payload
     * @return array{user_ids: list<int>, books: list<array{id: int|string, stock: int|string|null}>, reservations: list<array{id: int|string, user_id: int|string, book_id: int|string, status: string, created_at: string|null, updated_at: string|null}>}
     */
    private function validatePayload(array $payload): array
    {
        return Validator::make($payload, [
            'format' => ['required', 'string', Rule::in(['readora-legacy-cutover'])],
            'version' => ['required', 'integer', Rule::in([1])],
            'user_ids' => ['present', 'array'],
            'user_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'books' => ['present', 'array'],
            'books.*' => ['required', 'array:id,stock'],
            'books.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'books.*.stock' => ['present', 'nullable', 'integer', 'min:0'],
            'reservations' => ['present', 'array'],
            'reservations.*' => ['required', 'array:id,user_id,book_id,status,created_at,updated_at'],
            'reservations.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'reservations.*.user_id' => ['required', 'integer', 'min:1'],
            'reservations.*.book_id' => ['required', 'integer', 'min:1'],
            'reservations.*.status' => ['required', 'string', Rule::in(['active', 'cancelled', 'purchased'])],
            'reservations.*.created_at' => ['present', 'nullable', 'date'],
            'reservations.*.updated_at' => ['present', 'nullable', 'date'],
        ])->validate();
    }

    /** @param array{user_ids: list<int|string>, books: list<array{id: int|string, stock: int|string|null}>, reservations: list<array{id: int|string, user_id: int|string, book_id: int|string, status: string, created_at: string|null, updated_at: string|null}>} $data */
    private function validateReferences(array $data): void
    {
        $bookIds = array_fill_keys(array_map('intval', array_column($data['books'], 'id')), true);
        $userIds = array_fill_keys(array_map('intval', $data['user_ids']), true);

        foreach ($data['reservations'] as $reservation) {
            if (! isset($bookIds[(int) $reservation['book_id']])) {
                throw new RuntimeException('Reservation '.$reservation['id'].' refers to a book absent from the export.');
            }

            if (! isset($userIds[(int) $reservation['user_id']])) {
                throw new RuntimeException('Reservation '.$reservation['id'].' refers to a user absent from the export.');
            }
        }
    }

    /** @param array{user_ids: list<int|string>, books: list<array{id: int|string, stock: int|string|null}>, reservations: list<array{id: int|string, user_id: int|string, book_id: int|string, status: string, created_at: string|null, updated_at: string|null}>} $data
     * @return array{new_inventories: list<array{book_id: int, stock: int|null}>, new_reservations: list<array<string, int|string|null>>, inventories_unchanged: int, reservations_unchanged: int, conflicts: list<string>}
     */
    private function buildPlan(array $data): array
    {
        $plan = [
            'new_inventories' => [],
            'new_reservations' => [],
            'inventories_unchanged' => 0,
            'reservations_unchanged' => 0,
            'conflicts' => [],
        ];

        foreach ($data['books'] as $book) {
            $bookId = (int) $book['id'];
            $stock = $book['stock'] === null ? null : (int) $book['stock'];
            $existing = DB::table('inventories')->where('book_id', $bookId)->first();

            if ($existing === null) {
                $plan['new_inventories'][] = ['book_id' => $bookId, 'stock' => $stock];
            } elseif ($this->nullableInteger($existing->stock) === $stock) {
                $plan['inventories_unchanged']++;
            } else {
                $plan['conflicts'][] = 'inventory for book '.$bookId.' differs';
            }
        }

        foreach ($data['reservations'] as $legacyReservation) {
            $reservationId = (int) $legacyReservation['id'];
            $expected = [
                'id' => $reservationId,
                'user_id' => (int) $legacyReservation['user_id'],
                'book_id' => (int) $legacyReservation['book_id'],
                'status' => $legacyReservation['status'],
                'idempotency_key' => $this->legacyIdempotencyKey($reservationId),
                'created_at' => $legacyReservation['created_at'],
                'updated_at' => $legacyReservation['updated_at'],
            ];
            $existing = DB::table('reservations')->where('id', $reservationId)->first();

            if ($existing !== null) {
                if ($this->reservationMatches($existing, $expected)) {
                    $plan['reservations_unchanged']++;
                } else {
                    $plan['conflicts'][] = 'reservation '.$reservationId.' differs';
                }

                continue;
            }

            $keyOwner = DB::table('reservations')
                ->where('user_id', $expected['user_id'])
                ->where('idempotency_key', $expected['idempotency_key'])
                ->first();

            if ($keyOwner !== null) {
                $plan['conflicts'][] = 'idempotency key for reservation '.$reservationId.' is already used';

                continue;
            }

            $plan['new_reservations'][] = $expected;
        }

        return $plan;
    }

    /** @param array<string, int|string|null> $expected */
    private function reservationMatches(object $existing, array $expected): bool
    {
        return (int) $existing->id === $expected['id']
            && (int) $existing->user_id === $expected['user_id']
            && (int) $existing->book_id === $expected['book_id']
            && (string) $existing->status === $expected['status']
            && (string) $existing->idempotency_key === $expected['idempotency_key']
            && $this->nullableString($existing->created_at) === $expected['created_at']
            && $this->nullableString($existing->updated_at) === $expected['updated_at'];
    }

    private function nullableInteger(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    private function legacyIdempotencyKey(int $reservationId): string
    {
        return 'legacy-readora-reservation-'.$reservationId;
    }
}
