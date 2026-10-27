<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

function createReservationForAdmin(string $status = 'active'): array
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $customer = User::factory()->create();
    $category = Category::create(['name' => 'Fiction']);
    $book = Book::create([
        'title' => 'A Useful Book',
        'author' => 'A. Writer',
        'description' => 'A useful description.',
        'isbn' => '0001234567890',
        'price' => '19.95',
        'stock' => 4,
        'category_id' => $category->id,
    ]);

    return [$admin, $customer, $book, reservationApiRecord(610, $customer->id, $book->id, $status)];
}

function fakeAdminReservationApi(callable $handler): void
{
    config([
        'services.inventory_api.url' => 'http://inventory.test:8001',
        'services.inventory_api.token' => 'test-service-token',
        'services.inventory_api.identity_secret' => 'test-identity-secret',
    ]);

    Http::preventStrayRequests();
    Http::fake($handler);
}

test('an admin sees API reservations and can mark an active reservation as purchased without changing local stock', function () {
    [$admin, $customer, $book, $record] = createReservationForAdmin();

    fakeAdminReservationApi(function (HttpRequest $request) use ($record) {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($request->method() === 'GET' && $path === '/api/v1/admin/reservations') {
            return Http::response(reservationApiList([$record]));
        }

        if ($request->method() === 'PATCH' && $path === '/api/v1/admin/reservations/610/purchase') {
            return Http::response(['data' => array_merge($record, ['status' => 'purchased'])]);
        }

        return Http::response(['message' => 'Not found'], 404);
    });

    $this->actingAs($admin)
        ->get(route('admin.reservations.index'))
        ->assertOk()
        ->assertSee('A Useful Book')
        ->assertSee($customer->name)
        ->assertSee('Reservation #610')
        ->assertSee('Mark as purchased');

    $this->actingAs($admin)
        ->patch(route('admin.reservations.purchase', 610))
        ->assertRedirect(route('admin.reservations.index'))
        ->assertSessionHas('success');

    expect($book->fresh()->stock)->toBe(4);

    Http::assertSent(fn (HttpRequest $request): bool => $request->method() === 'PATCH'
        && $request->url() === 'http://inventory.test:8001/api/v1/admin/reservations/610/purchase'
        && $request->hasHeader('X-Service-Token', 'test-service-token')
        && $request->hasHeader('X-Readora-Role', 'admin')
        && $request->hasHeader('X-Readora-Signature'));
});

test('a non-admin cannot view or purchase reservations', function () {
    [, $customer] = createReservationForAdmin();

    fakeAdminReservationApi(fn () => Http::response(['data' => []]));

    $this->actingAs($customer)
        ->get(route('admin.reservations.index'))
        ->assertRedirect('/');

    $this->actingAs($customer)
        ->patch(route('admin.reservations.purchase', 610))
        ->assertRedirect('/');

    Http::assertNothingSent();
});

test('a cancelled reservation cannot be marked as purchased', function () {
    [$admin, , $book, $record] = createReservationForAdmin('cancelled');

    fakeAdminReservationApi(function (HttpRequest $request) use ($record) {
        if ($request->method() === 'PATCH') {
            return Http::response(['message' => 'Only an active reservation can be marked as purchased.'], 409);
        }

        if ($request->method() === 'GET' && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/admin/reservations') {
            return Http::response(reservationApiList([$record]));
        }

        return Http::response(['message' => 'Not found'], 404);
    });

    $this->actingAs($admin)
        ->patch(route('admin.reservations.purchase', 610))
        ->assertRedirect(route('admin.reservations.index'))
        ->assertSessionHas('info');

    expect($book->fresh()->stock)->toBe(4);
});

test('a purchase API timeout is not reported as a successful purchase', function () {
    [$admin] = createReservationForAdmin();

    fakeAdminReservationApi(fn (HttpRequest $request) => $request->method() === 'PATCH'
        ? Http::failedConnection()
        : Http::response(['data' => []]));

    $this->actingAs($admin)
        ->patch(route('admin.reservations.purchase', 610))
        ->assertRedirect(route('admin.reservations.index'))
        ->assertSessionHas('error')
        ->assertSessionMissing('success');
});
