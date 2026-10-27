<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

function createBookForReservation(
    ?int $stock,
    string $title = 'A Useful Book',
    string $isbn = '0001234567890',
    ?string $image = null
): Book {
    $category = Category::create(['name' => 'Fiction']);

    return Book::create([
        'title' => $title,
        'author' => 'A. Writer',
        'description' => 'A useful description.',
        'isbn' => $isbn,
        'price' => '19.95',
        'stock' => $stock,
        'image' => $image,
        'category_id' => $category->id,
    ]);
}

function fakeReadoraReservationApi(callable $handler, ?int $stock = 2): void
{
    config([
        'services.inventory_api.url' => 'http://inventory.test:8001',
        'services.inventory_api.token' => 'test-service-token',
        'services.inventory_api.identity_secret' => 'test-identity-secret',
    ]);

    Http::preventStrayRequests();
    Http::fake(function (HttpRequest $request) use ($handler, $stock) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if (is_string($path) && str_starts_with($path, '/api/v1/inventory/books/')) {
            $bookId = (int) basename($path);

            return Http::response([
                'data' => [
                    'book_id' => $bookId,
                    'stock' => $stock,
                    'available' => $stock !== null && $stock > 0,
                ],
            ]);
        }

        return $handler($request);
    });
}

test('an authenticated user reserves through the API and receives pickup and payment information', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(2);
    $record = reservationApiRecord(501, $user->id, $book->id);

    fakeReadoraReservationApi(function (HttpRequest $request) use ($record) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($request->method() === 'POST' && $path === '/api/v1/reservations') {
            return Http::response(['data' => $record], 201);
        }

        if ($request->method() === 'GET' && $path === '/api/v1/reservations/501') {
            return Http::response(['data' => $record]);
        }

        return Http::response(['message' => 'Not found'], 404);
    });

    $this->actingAs($user)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect(route('reservations.confirmation', 501));

    expect(Reservation::query()->count())->toBe(0)
        ->and($book->fresh()->stock)->toBe(2);

    $this->actingAs($user)
        ->get(route('reservations.confirmation', 501))
        ->assertOk()
        ->assertSee('Reservation confirmed')
        ->assertSee('Contact Readora to arrange pickup and payment.');

    Http::assertSent(fn (HttpRequest $request): bool => $request->method() === 'POST'
        && $request->url() === 'http://inventory.test:8001/api/v1/reservations'
        && $request->data() === ['book_id' => $book->id]
        && $request->hasHeader('X-Service-Token', 'test-service-token')
        && $request->hasHeader('X-Readora-Role', 'user')
        && $request->hasHeader('Idempotency-Key'));
});

test('unknown, zero, and insufficient stock API responses do not create a local reservation or claim success', function (?int $legacyStock) {
    $user = User::factory()->create();
    $book = createBookForReservation($legacyStock);

    fakeReadoraReservationApi(fn () => Http::response([
        'message' => 'The requested book is unavailable.',
    ], 409));

    $this->actingAs($user)
        ->from('/books/'.$book->id)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect('/books/'.$book->id)
        ->assertSessionHas('reservation_error');

    expect(Reservation::query()->count())->toBe(0)
        ->and($book->fresh()->stock)->toBe($legacyStock);
})->with([null, 0, 2]);

test('a retry after an uncertain API timeout reuses the same idempotency key', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(1);
    $record = reservationApiRecord(502, $user->id, $book->id);
    $requestCount = 0;
    $keys = [];

    fakeReadoraReservationApi(function (HttpRequest $request) use (&$requestCount, &$keys, $record) {
        if ($request->method() !== 'POST') {
            return Http::response(['message' => 'Not found'], 404);
        }

        $keys[] = $request->header('Idempotency-Key')[0] ?? '';

        if (++$requestCount === 1) {
            return Http::failedConnection();
        }

        return Http::response(['data' => $record], 200);
    });

    $this->actingAs($user)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect('/books/'.$book->id)
        ->assertSessionHas('reservation_error');

    $this->actingAs($user)
        ->post(route('books.reservations.store', $book))
        ->assertRedirect(route('reservations.confirmation', 502));

    expect($keys)->toHaveCount(2)
        ->and($keys[0])->not->toBe('')
        ->and($keys[1])->toBe($keys[0])
        ->and(Reservation::query()->count())->toBe(0)
        ->and($book->fresh()->stock)->toBe(1);
});

test('replaying a successful reservation form reuses its idempotency key', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(2);
    $record = reservationApiRecord(507, $user->id, $book->id);
    $keys = [];

    fakeReadoraReservationApi(function (HttpRequest $request) use (&$keys, $record) {
        if ($request->method() !== 'POST') {
            return Http::response(['message' => 'Not found'], 404);
        }

        $keys[] = $request->header('Idempotency-Key')[0] ?? '';

        return Http::response(['data' => $record], count($keys) === 1 ? 201 : 200);
    });

    $idempotencyKey = (string) Str::uuid();

    $this->actingAs($user)
        ->post(route('books.reservations.store', $book), ['idempotency_key' => $idempotencyKey])
        ->assertRedirect(route('reservations.confirmation', 507));

    $this->actingAs($user)
        ->post(route('books.reservations.store', $book), ['idempotency_key' => $idempotencyKey])
        ->assertRedirect(route('reservations.confirmation', 507));

    expect($keys)->toBe([$idempotencyKey, $idempotencyKey])
        ->and(Reservation::query()->count())->toBe(0)
        ->and($book->fresh()->stock)->toBe(2);
});

test('the user reservation list reads only the API response and shows purchased state', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $ownBook = createBookForReservation(8, 'My Purchased Book', '0001111111111', 'storage/books/my-book.jpg');
    $cancelledBook = createBookForReservation(8, 'My Cancelled Book', '0002222222222');
    $legacyBook = createBookForReservation(8, 'Legacy Only Book', '0003333333333');
    $records = [
        reservationApiRecord(503, $user->id, $ownBook->id, 'purchased'),
        reservationApiRecord(508, $user->id, $cancelledBook->id, 'cancelled'),
    ];

    fakeReadoraReservationApi(function (HttpRequest $request) use ($records) {
        if ($request->method() === 'GET' && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/reservations') {
            return Http::response(reservationApiList($records));
        }

        return Http::response(['message' => 'Not found'], 404);
    });

    $user->reservations()->create(['book_id' => $legacyBook->id]);

    $this->actingAs($user)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('My Reservations')
        ->assertSee('My Purchased Book')
        ->assertSee('My Cancelled Book')
        ->assertSee('/storage/books/my-book.jpg')
        ->assertSee('Reservation #503')
        ->assertSee('Reservation #508')
        ->assertSee('Purchased')
        ->assertSee('Cancelled')
        ->assertDontSee('Cancel Reservation')
        ->assertDontSee('Legacy Only Book');

    expect($otherUser->reservations()->count())->toBe(0);
});

test('a guest must sign in before viewing or creating reservations', function () {
    $book = createBookForReservation(1);
    fakeReadoraReservationApi(fn () => Http::response(['data' => []]));

    $this->get(route('reservations.index'))->assertRedirect(route('login'));
    $this->post(route('books.reservations.store', $book))->assertRedirect(route('login'));

    Http::assertNothingSent();
});

test('reservation confirmation and cancellation reject another user through API ownership checks', function () {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $book = createBookForReservation(1);
    $record = reservationApiRecord(504, $owner->id, $book->id);

    fakeReadoraReservationApi(function (HttpRequest $request) {
        return Http::response(['message' => 'You cannot access this reservation.'], 403);
    });

    $this->actingAs($anotherUser)
        ->get(route('reservations.confirmation', 504))
        ->assertForbidden();

    $this->actingAs($anotherUser)
        ->delete(route('reservations.cancel', 504))
        ->assertForbidden();

    expect($book->fresh()->stock)->toBe(1);
});

test('cancelling through the API restores once and a repeated cancellation does not restore again', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(0);
    $record = reservationApiRecord(505, $user->id, $book->id);
    $status = 'active';

    fakeReadoraReservationApi(function (HttpRequest $request) use (&$status, $record) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($request->method() === 'DELETE' && $path === '/api/v1/reservations/505') {
            if ($status !== 'active') {
                return Http::response(['message' => 'Only an active reservation can be cancelled.'], 409);
            }

            $status = 'cancelled';

            return Http::response(['data' => array_merge($record, ['status' => $status])]);
        }

        if ($request->method() === 'GET' && $path === '/api/v1/reservations/505') {
            return Http::response(['data' => array_merge($record, ['status' => $status])]);
        }

        if ($request->method() === 'GET' && $path === '/api/v1/reservations') {
            return Http::response(reservationApiList([array_merge($record, ['status' => $status])]));
        }

        return Http::response(['message' => 'Not found'], 404);
    });

    $this->actingAs($user)
        ->delete(route('reservations.cancel', 505))
        ->assertRedirect(route('reservations.index'))
        ->assertSessionHas('success');

    $this->actingAs($user)
        ->delete(route('reservations.cancel', 505))
        ->assertRedirect(route('reservations.index'))
        ->assertSessionHas('info');

    expect($status)->toBe('cancelled')
        ->and($book->fresh()->stock)->toBe(0)
        ->and(Reservation::query()->count())->toBe(0);
});

test('a purchased reservation cannot be cancelled', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(0);
    $record = reservationApiRecord(506, $user->id, $book->id, 'purchased');

    fakeReadoraReservationApi(function (HttpRequest $request) use ($record) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($request->method() === 'DELETE') {
            return Http::response(['message' => 'Only an active reservation can be cancelled.'], 409);
        }

        if ($request->method() === 'GET' && $path === '/api/v1/reservations/506') {
            return Http::response(['data' => $record]);
        }

        return Http::response(['message' => 'Not found'], 404);
    });

    $this->actingAs($user)
        ->delete(route('reservations.cancel', 506))
        ->assertRedirect(route('reservations.index'))
        ->assertSessionHas('info');

    expect($book->fresh()->stock)->toBe(0)
        ->and(Reservation::query()->count())->toBe(0);
});

test('reservation history fails safely instead of showing stale local records when the API is unavailable', function () {
    $user = User::factory()->create();
    $book = createBookForReservation(1, 'Legacy Only Reservation');
    $user->reservations()->create(['book_id' => $book->id]);

    fakeReadoraReservationApi(fn () => Http::response(['message' => 'Unavailable'], 503));

    $this->actingAs($user)
        ->get(route('reservations.index'))
        ->assertOk()
        ->assertSee('Your reservations could not be loaded right now')
        ->assertDontSee('Legacy Only Reservation');
});
