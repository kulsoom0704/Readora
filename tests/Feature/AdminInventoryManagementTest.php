<?php

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function createBookForAdminInventoryManagement(?int $legacyStock = null): Book
{
    $category = Category::create(['name' => 'Inventory Management']);

    return Book::create([
        'title' => 'Inventory Management Book',
        'author' => 'A. Writer',
        'description' => 'A test book for admin inventory management.',
        'isbn' => '9987654321098',
        'price' => '17.50',
        'stock' => $legacyStock,
        'category_id' => $category->id,
    ]);
}

function createAdminForInventoryManagement(): User
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

it('lets an admin view API stock, ISBN, and an initialization form for missing inventory', function () {
    $admin = createAdminForInventoryManagement();
    $book = createBookForAdminInventoryManagement(87);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response(['message' => 'Not Found'], 404),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.books.index'))
        ->assertOk()
        ->assertSee($book->title)
        ->assertSee($book->isbn)
        ->assertSee('Not configured')
        ->assertSee('Set stock quantity')
        ->assertDontSee('87 available');
});

it('updates API stock using the signed admin identity and leaves the legacy stock untouched', function () {
    $admin = createAdminForInventoryManagement();
    $book = createBookForAdminInventoryManagement(87);

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response([
            'data' => ['book_id' => $book->id, 'stock' => 6, 'available' => true],
        ]),
    ]);

    $this->actingAs($admin)
        ->put(route('admin.inventory.update', $book), ['stock' => [$book->id => '6']])
        ->assertRedirect(route('admin.books.index'))
        ->assertSessionHas('success', 'Inventory updated successfully.');

    expect($book->fresh()->stock)->toBe(87);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request->url() === 'http://inventory.test:8001/api/v1/inventory/books/'.$book->id
        && $request->data() === ['stock' => 6]
        && $request->hasHeader('X-Service-Token', 'test-service-token')
        && $request->hasHeader('X-Readora-Role', 'admin')
        && $request->hasHeader('X-Readora-User-Id', (string) $admin->id)
        && $request->hasHeader('X-Readora-Signature'));
});

it('rejects negative stock input', function () {
    $admin = createAdminForInventoryManagement();
    $book = createBookForAdminInventoryManagement();

    Http::fake();

    $this->actingAs($admin)
        ->put(route('admin.inventory.update', $book), ['stock' => [$book->id => '-1']])
        ->assertSessionHasErrors('stock.'.$book->id);

    Http::assertNothingSent();
});

it('rejects non-integer and invalid stock input', function (string $value) {
    $admin = createAdminForInventoryManagement();
    $book = createBookForAdminInventoryManagement();

    Http::fake();

    $this->actingAs($admin)
        ->put(route('admin.inventory.update', $book), ['stock' => [$book->id => $value]])
        ->assertSessionHasErrors('stock.'.$book->id);

    Http::assertNothingSent();
})->with(['1.5', 'not-a-number']);

it('prevents guests and non-admin users from viewing or changing inventory', function () {
    $book = createBookForAdminInventoryManagement();
    Http::fake();

    $this->get(route('admin.books.index'))->assertRedirect('/');
    $this->put(route('admin.inventory.update', $book), ['stock' => [$book->id => '3']])->assertRedirect('/');

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.books.index'))
        ->assertRedirect('/');
    $this->actingAs($user)
        ->put(route('admin.inventory.update', $book), ['stock' => [$book->id => '3']])
        ->assertRedirect('/');

    Http::assertNothingSent();
});

it('does not show success when the Inventory API returns an error', function () {
    $admin = createAdminForInventoryManagement();
    $book = createBookForAdminInventoryManagement();

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response(['message' => 'Unavailable'], 503),
    ]);

    $this->actingAs($admin)
        ->followingRedirects()
        ->put(route('admin.inventory.update', $book), ['stock' => [$book->id => '3']])
        ->assertOk()
        ->assertSee('The Inventory API rejected the stock update (HTTP 503). The update was not confirmed.')
        ->assertDontSee('Inventory updated successfully.');
});

it('does not show success when the Inventory API times out', function () {
    $admin = createAdminForInventoryManagement();
    $book = createBookForAdminInventoryManagement();

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::failedConnection(),
    ]);

    $this->actingAs($admin)
        ->followingRedirects()
        ->put(route('admin.inventory.update', $book), ['stock' => [$book->id => '3']])
        ->assertOk()
        ->assertSee('The Inventory API is unavailable or timed out. Stock was not confirmed as updated.')
        ->assertDontSee('Inventory updated successfully.');
});

it('can initialize a missing API inventory record through the update endpoint', function () {
    $admin = createAdminForInventoryManagement();
    $book = createBookForAdminInventoryManagement();

    Http::fake([
        'http://inventory.test:8001/api/v1/inventory/books/'.$book->id => Http::response([
            'data' => ['book_id' => $book->id, 'stock' => 2, 'available' => true],
        ]),
    ]);

    $this->actingAs($admin)
        ->put(route('admin.inventory.update', $book), ['stock' => [$book->id => '2']])
        ->assertSessionHas('success', 'Inventory updated successfully.');

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request->url() === 'http://inventory.test:8001/api/v1/inventory/books/'.$book->id);
});
