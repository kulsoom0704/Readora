<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function createBookForInventoryApiRead(?int $legacyStock): Book
{
    $category = Category::create(['name' => 'Inventory Read Test']);

    return Book::create([
        'title' => 'Inventory Read Book',
        'author' => 'A. Writer',
        'description' => 'A test book for inventory reads.',
        'isbn' => '9987654321098',
        'price' => '17.50',
        'stock' => $legacyStock,
        'category_id' => $category->id,
    ]);
}

function markUserAsAdminForInventoryRead(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

beforeEach(function () {
    config([
        'services.inventory_api.url' => 'http://inventory.test:8001',
        'services.inventory_api.token' => 'test-service-token',
        'services.inventory_api.identity_secret' => 'test-identity-secret',
    ]);

    Http::preventStrayRequests();
});

it('displays verified API stock on the book detail page instead of the legacy value', function () {
    $book = createBookForInventoryApiRead(73);
    $user = User::factory()->create();

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response([
            'data' => ['book_id' => $book->id, 'stock' => 2, 'available' => true],
        ]),
        'http://inventory.test:8001/api/v1/reservations*' => Http::response(reservationApiList([])),
    ]);

    $this->actingAs($user)
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('2 available')
        ->assertDontSee('73 available')
        ->assertSee('Reserve one copy');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && $request->url() === 'http://inventory.test:8001/api/v1/inventory/books/'.$book->id
        && $request->hasHeader('X-Service-Token', 'test-service-token'));
});

it('treats null API stock as unknown and does not offer a reservation', function () {
    $book = createBookForInventoryApiRead(8);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response([
            'data' => ['book_id' => $book->id, 'stock' => null, 'available' => false],
        ]),
        'http://inventory.test:8001/api/v1/reservations*' => Http::response(reservationApiList([])),
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('Stock is unknown')
        ->assertDontSee('Reserve one copy')
        ->assertDontSee('0 available');
});

it('treats a missing API inventory record as unavailable without using legacy stock', function () {
    $book = createBookForInventoryApiRead(9);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response(['message' => 'Not Found'], 404),
        'http://inventory.test:8001/api/v1/reservations*' => Http::response(reservationApiList([])),
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('Stock has not been configured')
        ->assertDontSee('Reserve one copy')
        ->assertDontSee('9 available');
});

it('shows an unavailable state when the Inventory API returns an error', function () {
    $book = createBookForInventoryApiRead(9);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response(['message' => 'Unavailable'], 503),
        'http://inventory.test:8001/api/v1/reservations*' => Http::response(reservationApiList([])),
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('Availability could not be checked right now')
        ->assertDontSee('Reserve one copy')
        ->assertDontSee('9 available');
});

it('shows an unavailable state when the Inventory API times out', function () {
    $book = createBookForInventoryApiRead(9);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::failedConnection(),
        'http://inventory.test:8001/api/v1/reservations*' => Http::response(reservationApiList([])),
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/books/'.$book->id)
        ->assertOk()
        ->assertSee('Availability could not be checked right now')
        ->assertDontSee('Reserve one copy')
        ->assertDontSee('9 available');
});

it('displays API stock in the admin book list instead of the legacy column', function () {
    $admin = markUserAsAdminForInventoryRead();
    $book = createBookForInventoryApiRead(23);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response([
            'data' => ['book_id' => $book->id, 'stock' => 4, 'available' => true],
        ]),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.books.index'))
        ->assertOk()
        ->assertSee('Stock (Inventory API)')
        ->assertSee('4')
        ->assertDontSee('23');
});

it('shows API stock read-only in the admin edit form', function () {
    $admin = markUserAsAdminForInventoryRead();
    $book = createBookForInventoryApiRead(23);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response([
            'data' => ['book_id' => $book->id, 'stock' => 6, 'available' => true],
        ]),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.books.edit', $book))
        ->assertOk()
        ->assertSee('Stock (Inventory API)')
        ->assertSee('6 available')
        ->assertDontSee('23 available')
        ->assertDontSee('name="stock"', false);
});
