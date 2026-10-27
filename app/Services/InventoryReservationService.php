<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class InventoryReservationService
{
    private const MAXIMUM_RESERVATION_PAGES = 1000;

    public function __construct(private readonly InventoryApiClient $client) {}

    public function reserve(User $user, Book $book, string $idempotencyKey): InventoryReservationRecord
    {
        $response = $this->client->request(
            'POST',
            '/api/v1/reservations',
            ['book_id' => (int) $book->getKey()],
            $user,
            $idempotencyKey,
        );
        $row = $this->validatedReservation($response->json('data'));

        if ($row['user_id'] !== (int) $user->getKey() || $row['book_id'] !== (int) $book->getKey()) {
            throw new InventoryApiException('The Inventory API returned a reservation for a different user or book.');
        }

        return $this->mapReservations([$row])->first();
    }

    /** @return Collection<int, InventoryReservationRecord> */
    public function forUser(User $user): Collection
    {
        $rows = $this->allReservations('/api/v1/reservations', $user);

        foreach ($rows as $row) {
            if ($row['user_id'] !== (int) $user->getKey()) {
                throw new InventoryApiException('The Inventory API returned reservations for a different user.');
            }
        }

        return $this->mapReservations($rows);
    }

    /** @return Collection<int, InventoryReservationRecord> */
    public function forAdmin(User $admin): Collection
    {
        return $this->mapReservations($this->allReservations('/api/v1/admin/reservations', $admin), includeUsers: true);
    }

    public function findForUser(User $user, int $reservationId): InventoryReservationRecord
    {
        $response = $this->client->request('GET', '/api/v1/reservations/'.$reservationId, identity: $user);
        $row = $this->validatedReservation($response->json('data'));

        if ($row['id'] !== $reservationId || $row['user_id'] !== (int) $user->getKey()) {
            throw new InventoryApiException('The Inventory API returned a reservation that does not belong to this user.');
        }

        return $this->mapReservations([$row])->first();
    }

    /** @return array{reservation: InventoryReservationRecord, newly_cancelled: bool} */
    public function cancelForUser(User $user, int $reservationId): array
    {
        try {
            $response = $this->client->request('DELETE', '/api/v1/reservations/'.$reservationId, identity: $user);
        } catch (InventoryApiException $exception) {
            if ($exception->statusCode !== 409) {
                throw $exception;
            }

            $current = $this->findForUser($user, $reservationId);

            if ($current->status === 'cancelled') {
                return ['reservation' => $current, 'newly_cancelled' => false];
            }

            throw $exception;
        }

        $row = $this->validatedReservation($response->json('data'));

        if (
            $row['id'] !== $reservationId
            || $row['user_id'] !== (int) $user->getKey()
            || $row['status'] !== 'cancelled'
        ) {
            throw new InventoryApiException('The Inventory API did not confirm cancellation of this reservation.');
        }

        return [
            'reservation' => $this->mapReservations([$row])->first(),
            'newly_cancelled' => true,
        ];
    }

    public function purchase(User $admin, int $reservationId): InventoryReservationRecord
    {
        try {
            $response = $this->client->request(
                'PATCH',
                '/api/v1/admin/reservations/'.$reservationId.'/purchase',
                identity: $admin,
            );
        } catch (InventoryApiException $exception) {
            if ($exception->statusCode !== 409) {
                throw $exception;
            }

            $current = $this->forAdmin($admin)->first(
                fn (InventoryReservationRecord $reservation): bool => $reservation->id === $reservationId
            );

            if ($current instanceof InventoryReservationRecord && $current->status === 'purchased') {
                return $current;
            }

            throw $exception;
        }

        $row = $this->validatedReservation($response->json('data'));

        if ($row['id'] !== $reservationId || $row['status'] !== 'purchased') {
            throw new InventoryApiException('The Inventory API did not confirm this reservation purchase.');
        }

        return $this->mapReservations([$row], includeUsers: true)->first();
    }

    public function userHasPurchasedBook(User $user, int $bookId): bool
    {
        return $this->forUser($user)->contains(
            fn (InventoryReservationRecord $reservation): bool => $reservation->book_id === $bookId
                && $reservation->status === 'purchased'
        );
    }

    /** @return list<array{id: int, user_id: int, book_id: int, status: string, created_at: string, updated_at: string}> */
    private function allReservations(string $path, User $identity): array
    {
        $reservations = [];

        for ($page = 1; $page <= self::MAXIMUM_RESERVATION_PAGES; $page++) {
            $pagePath = $page === 1 ? $path : $path.'?page='.$page;
            $payload = $this->client->request('GET', $pagePath, identity: $identity)->json();

            if (! is_array($payload)) {
                throw new InventoryApiException('The Inventory API returned an invalid reservation list.');
            }

            $rows = $payload['data'] ?? null;
            $meta = $payload['meta'] ?? null;
            $lastPage = is_array($meta)
                ? filter_var($meta['last_page'] ?? null, FILTER_VALIDATE_INT)
                : false;

            if (
                ! is_array($rows)
                || ! array_is_list($rows)
                || $lastPage === false
                || $lastPage < $page
                || $lastPage > self::MAXIMUM_RESERVATION_PAGES
            ) {
                throw new InventoryApiException('The Inventory API returned an invalid reservation list.');
            }

            foreach ($rows as $row) {
                $reservations[] = $this->validatedReservation($row);
            }

            if ($page === $lastPage) {
                return $reservations;
            }
        }

        throw new InventoryApiException('The Inventory API reservation list exceeded the supported page limit.');
    }

    /**
     * @return array{id: int, user_id: int, book_id: int, status: string, created_at: string, updated_at: string}
     */
    private function validatedReservation(mixed $row): array
    {
        if (! is_array($row)) {
            throw new InventoryApiException('The Inventory API returned an invalid reservation.');
        }

        $id = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT);
        $userId = filter_var($row['user_id'] ?? null, FILTER_VALIDATE_INT);
        $bookId = filter_var($row['book_id'] ?? null, FILTER_VALIDATE_INT);
        $status = $row['status'] ?? null;
        $createdAt = $row['created_at'] ?? null;
        $updatedAt = $row['updated_at'] ?? null;

        if (
            $id === false || $id < 1
            || $userId === false || $userId < 1
            || $bookId === false || $bookId < 1
            || ! in_array($status, ['active', 'cancelled', 'purchased'], true)
            || ! is_string($createdAt)
            || ! is_string($updatedAt)
        ) {
            throw new InventoryApiException('The Inventory API returned an invalid reservation.');
        }

        try {
            Carbon::parse($createdAt);
            Carbon::parse($updatedAt);
        } catch (Throwable $exception) {
            throw new InventoryApiException('The Inventory API returned invalid reservation timestamps.', previous: $exception);
        }

        return [
            'id' => $id,
            'user_id' => $userId,
            'book_id' => $bookId,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * @param  list<array{id: int, user_id: int, book_id: int, status: string, created_at: string, updated_at: string}>  $rows
     * @return Collection<int, InventoryReservationRecord>
     */
    private function mapReservations(array $rows, bool $includeUsers = false): Collection
    {
        $bookIds = array_values(array_unique(array_column($rows, 'book_id')));
        $books = Book::query()->whereIn('id', $bookIds)->get()->keyBy(fn (Book $book): int => (int) $book->getKey());
        $users = $includeUsers
            ? User::query()->whereIn('id', array_values(array_unique(array_column($rows, 'user_id'))))
                ->get()
                ->keyBy(fn (User $user): int => (int) $user->getKey())
            : collect();

        return collect($rows)
            ->map(fn (array $row): InventoryReservationRecord => new InventoryReservationRecord(
                id: $row['id'],
                user_id: $row['user_id'],
                book_id: $row['book_id'],
                status: $row['status'],
                created_at: Carbon::parse($row['created_at']),
                updated_at: Carbon::parse($row['updated_at']),
                book: $books->get($row['book_id']),
                user: $users->get($row['user_id']),
            ));
    }
}
