<?php

use App\Models\Inventory;
use App\Models\Reservation;

/** @return array<string, string> */
function signedInventoryApiHeaders(
    string $method,
    string $uri,
    int $userId = 1,
    string $role = 'user',
    string $body = '[]',
    string $idempotencyKey = '',
): array {
    $timestamp = (string) now()->timestamp;
    $path = parse_url($uri, PHP_URL_PATH);
    $message = implode("\n", [
        strtoupper($method),
        $path,
        $timestamp,
        (string) $userId,
        $role,
        $idempotencyKey,
        hash('sha256', $body),
    ]);

    $headers = [
        'X-Service-Token' => 'testing-service-token',
        'X-Readora-User-Id' => (string) $userId,
        'X-Readora-Role' => $role,
        'X-Readora-Timestamp' => $timestamp,
        'X-Readora-Signature' => hash_hmac('sha256', $message, 'testing-identity-secret'),
        'Idempotency-Key' => $idempotencyKey,
    ];

    return $headers;
}

/** @param array<string, mixed> $payload */
function signedInventoryApiJson(
    string $method,
    string $uri,
    array $payload,
    int $userId = 1,
    string $role = 'user',
    string $idempotencyKey = '',
): array {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return signedInventoryApiHeaders($method, $uri, $userId, $role, $body, $idempotencyKey);
}

it('creates and updates inventory and reports availability', function () {
    $uri = '/api/v1/inventory/books/41';
    $this->withHeaders(signedInventoryApiJson('PUT', $uri, ['stock' => 3], role: 'admin'))
        ->putJson($uri, ['stock' => 3])
        ->assertOk()
        ->assertJsonPath('data.book_id', 41)
        ->assertJsonPath('data.stock', 3)
        ->assertJsonPath('data.available', true);

    $this->withHeaders(signedInventoryApiHeaders('GET', $uri))
        ->getJson($uri)
        ->assertOk()
        ->assertJsonPath('data.stock', 3);

    $this->withHeaders(signedInventoryApiJson('PUT', $uri, ['stock' => 0], role: 'admin'))
        ->putJson($uri, ['stock' => 0])
        ->assertOk()
        ->assertJsonPath('data.available', false);

    $this->withHeaders(signedInventoryApiJson('PUT', $uri, ['stock' => null], role: 'admin'))
        ->putJson($uri, ['stock' => null])
        ->assertOk()
        ->assertJsonPath('data.stock', null)
        ->assertJsonPath('data.available', false);
});

it('rejects negative or missing stock values', function () {
    $uri = '/api/v1/inventory/books/42';

    $this->withHeaders(signedInventoryApiJson('PUT', $uri, ['stock' => -1], role: 'admin'))
        ->putJson($uri, ['stock' => -1])
        ->assertUnprocessable();

    $this->withHeaders(signedInventoryApiJson('PUT', $uri, [], role: 'admin'))
        ->putJson($uri, [])
        ->assertUnprocessable();

    $this->assertDatabaseMissing('inventories', ['book_id' => 42]);
});

it('returns not found when an inventory record does not exist', function () {
    $uri = '/api/v1/inventory/books/43';

    $this->withHeaders(signedInventoryApiHeaders('GET', $uri))
        ->getJson($uri)
        ->assertNotFound();
});

it('returns not found for missing reservations', function () {
    $uri = '/api/v1/reservations/999';

    $this->withHeaders(signedInventoryApiHeaders('GET', $uri))
        ->getJson($uri)
        ->assertNotFound();
});

it('creates a reservation and reduces stock by one', function () {
    Inventory::create(['book_id' => 10, 'stock' => 2]);
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 10];

    $response = $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'reserve-10-a'))
        ->postJson($uri, $payload);

    $response->assertCreated()
        ->assertJsonPath('data.user_id', 1)
        ->assertJsonPath('data.book_id', 10)
        ->assertJsonPath('data.status', Reservation::STATUS_ACTIVE);

    expect(Inventory::where('book_id', 10)->value('stock'))->toBe(1);
    expect(Reservation::count())->toBe(1);
});

it('rejects unknown, zero, and insufficient stock without over-reserving', function () {
    Inventory::create(['book_id' => 20, 'stock' => null]);
    Inventory::create(['book_id' => 21, 'stock' => 0]);
    Inventory::create(['book_id' => 22, 'stock' => 1]);

    foreach ([20, 21] as $bookId) {
        $uri = '/api/v1/reservations';
        $payload = ['book_id' => $bookId];
        $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'unavailable-'.$bookId))
            ->postJson($uri, $payload)
            ->assertStatus(409);
    }

    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 22];
    $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'only-copy'))
        ->postJson($uri, $payload)
        ->assertCreated();
    $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'second-copy'))
        ->postJson($uri, $payload)
        ->assertStatus(409);

    expect(Inventory::where('book_id', 20)->value('stock'))->toBeNull();
    expect(Inventory::where('book_id', 21)->value('stock'))->toBe(0);
    expect(Inventory::where('book_id', 22)->value('stock'))->toBe(0);
    expect(Reservation::count())->toBe(1);
});

it('allows only one reservation when two requests compete for the last copy', function () {
    Inventory::create(['book_id' => 23, 'stock' => 1]);
    $uri = '/api/v1/reservations';

    foreach (['last-copy-a', 'last-copy-b'] as $key) {
        $payload = ['book_id' => 23];
        $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: $key))
            ->postJson($uri, $payload)
            ->assertStatus($key === 'last-copy-a' ? 201 : 409);
    }

    expect(Inventory::where('book_id', 23)->value('stock'))->toBe(0);
    expect(Reservation::where('book_id', 23)->count())->toBe(1);
});

it('returns the original reservation on an idempotency retry without reducing stock again', function () {
    Inventory::create(['book_id' => 24, 'stock' => 2]);
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 24];
    $headers = signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'stable-retry-key');

    $first = $this->withHeaders($headers)->postJson($uri, $payload)->assertCreated();
    $second = $this->withHeaders($headers)->postJson($uri, $payload)->assertOk();

    expect($second->json('data.id'))->toBe($first->json('data.id'));
    expect(Inventory::where('book_id', 24)->value('stock'))->toBe(1);
    expect(Reservation::where('user_id', 1)->count())->toBe(1);
});

it('rejects reuse of an idempotency key for another book', function () {
    Inventory::create(['book_id' => 25, 'stock' => 1]);
    Inventory::create(['book_id' => 26, 'stock' => 1]);
    $uri = '/api/v1/reservations';
    $firstPayload = ['book_id' => 25];
    $this->withHeaders(signedInventoryApiJson('POST', $uri, $firstPayload, idempotencyKey: 'same-key'))
        ->postJson($uri, $firstPayload)
        ->assertCreated();

    $secondPayload = ['book_id' => 26];
    $this->withHeaders(signedInventoryApiJson('POST', $uri, $secondPayload, idempotencyKey: 'same-key'))
        ->postJson($uri, $secondPayload)
        ->assertStatus(409);

    expect(Inventory::where('book_id', 26)->value('stock'))->toBe(1);
});

it('requires a valid idempotency key and book identifier', function () {
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 0];

    $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload))
        ->postJson($uri, $payload)
        ->assertUnprocessable();
});

it('isolates reservation lists and prevents access or cancellation by another user', function () {
    Inventory::create(['book_id' => 30, 'stock' => 2]);
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 30];
    $response = $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, 31, idempotencyKey: 'user-31'))
        ->postJson($uri, $payload)
        ->assertCreated();
    $reservationId = $response->json('data.id');

    $this->withHeaders(signedInventoryApiHeaders('GET', $uri, 32))
        ->getJson($uri)
        ->assertOk()
        ->assertJsonCount(0, 'data');
    $this->withHeaders(signedInventoryApiHeaders('GET', $uri.'/'.$reservationId, 32))
        ->getJson($uri.'/'.$reservationId)
        ->assertForbidden();
    $this->withHeaders(signedInventoryApiHeaders('DELETE', $uri.'/'.$reservationId, 32))
        ->deleteJson($uri.'/'.$reservationId)
        ->assertForbidden();

    expect(Reservation::findOrFail($reservationId)->status)->toBe(Reservation::STATUS_ACTIVE);
});

it('cancels an active reservation and restores stock exactly once', function () {
    Inventory::create(['book_id' => 40, 'stock' => 1]);
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 40];
    $created = $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'cancel-once'))
        ->postJson($uri, $payload)
        ->assertCreated();
    $cancelUri = $uri.'/'.$created->json('data.id');

    $this->withHeaders(signedInventoryApiHeaders('DELETE', $cancelUri))
        ->deleteJson($cancelUri)
        ->assertOk()
        ->assertJsonPath('data.status', Reservation::STATUS_CANCELLED);
    $this->withHeaders(signedInventoryApiHeaders('DELETE', $cancelUri))
        ->deleteJson($cancelUri)
        ->assertStatus(409);

    expect(Inventory::where('book_id', 40)->value('stock'))->toBe(1);
});

it('does not allow stock to become unknown while an active reservation needs restoration', function () {
    Inventory::create(['book_id' => 44, 'stock' => 1]);
    $reservationUri = '/api/v1/reservations';
    $payload = ['book_id' => 44];
    $reservation = $this->withHeaders(signedInventoryApiJson('POST', $reservationUri, $payload, idempotencyKey: 'unknown-after-reserve'))
        ->postJson($reservationUri, $payload)
        ->assertCreated();

    $inventoryUri = '/api/v1/inventory/books/44';
    $this->withHeaders(signedInventoryApiJson('PUT', $inventoryUri, ['stock' => null], role: 'admin'))
        ->putJson($inventoryUri, ['stock' => null])
        ->assertStatus(409);

    $cancelUri = $reservationUri.'/'.$reservation->json('data.id');
    $this->withHeaders(signedInventoryApiHeaders('DELETE', $cancelUri))
        ->deleteJson($cancelUri)
        ->assertOk();

    expect(Inventory::where('book_id', 44)->value('stock'))->toBe(1);
});

it('prevents cancelling a purchased reservation without changing stock', function () {
    Inventory::create(['book_id' => 41, 'stock' => 1]);
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 41];
    $created = $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'purchase-first'))
        ->postJson($uri, $payload)
        ->assertCreated();
    $reservationId = $created->json('data.id');
    $purchaseUri = '/api/v1/admin/reservations/'.$reservationId.'/purchase';

    $this->withHeaders(signedInventoryApiHeaders('PATCH', $purchaseUri, role: 'admin'))
        ->patchJson($purchaseUri)
        ->assertOk()
        ->assertJsonPath('data.status', Reservation::STATUS_PURCHASED);
    $this->withHeaders(signedInventoryApiHeaders('DELETE', $uri.'/'.$reservationId))
        ->deleteJson($uri.'/'.$reservationId)
        ->assertStatus(409);

    expect(Inventory::where('book_id', 41)->value('stock'))->toBe(0);
});

it('does not change stock when marking a reservation purchased', function () {
    Inventory::create(['book_id' => 42, 'stock' => 2]);
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 42];
    $created = $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'purchase-no-stock-change'))
        ->postJson($uri, $payload)
        ->assertCreated();
    $purchaseUri = '/api/v1/admin/reservations/'.$created->json('data.id').'/purchase';

    $this->withHeaders(signedInventoryApiHeaders('PATCH', $purchaseUri, role: 'admin'))
        ->patchJson($purchaseUri)
        ->assertOk();

    expect(Inventory::where('book_id', 42)->value('stock'))->toBe(1);
});

it('cannot purchase a cancelled reservation', function () {
    Inventory::create(['book_id' => 43, 'stock' => 1]);
    $uri = '/api/v1/reservations';
    $payload = ['book_id' => 43];
    $created = $this->withHeaders(signedInventoryApiJson('POST', $uri, $payload, idempotencyKey: 'cancel-before-purchase'))
        ->postJson($uri, $payload)
        ->assertCreated();
    $reservationId = $created->json('data.id');
    $this->withHeaders(signedInventoryApiHeaders('DELETE', $uri.'/'.$reservationId))
        ->deleteJson($uri.'/'.$reservationId)
        ->assertOk();
    $purchaseUri = '/api/v1/admin/reservations/'.$reservationId.'/purchase';

    $this->withHeaders(signedInventoryApiHeaders('PATCH', $purchaseUri, role: 'admin'))
        ->patchJson($purchaseUri)
        ->assertStatus(409);
    expect(Reservation::findOrFail($reservationId)->status)->toBe(Reservation::STATUS_CANCELLED);
});

it('requires signed admin identity for stock changes and admin reservation operations', function () {
    $inventoryUri = '/api/v1/inventory/books/50';
    $this->withHeaders(signedInventoryApiJson('PUT', $inventoryUri, ['stock' => 5]))
        ->putJson($inventoryUri, ['stock' => 5])
        ->assertForbidden();

    $this->withHeaders(signedInventoryApiHeaders('GET', '/api/v1/admin/reservations'))
        ->getJson('/api/v1/admin/reservations')
        ->assertForbidden();
    $this->withHeaders(signedInventoryApiHeaders('GET', '/api/v1/admin/reservations', role: 'admin'))
        ->getJson('/api/v1/admin/reservations')
        ->assertOk();
});

it('requires a signed identity in addition to the service token', function () {
    $this->withHeader('X-Service-Token', 'testing-service-token')
        ->getJson('/api/v1/reservations')
        ->assertUnauthorized();
});

it('rejects a modified trusted identity assertion', function () {
    $uri = '/api/v1/reservations';
    $headers = signedInventoryApiHeaders('GET', $uri, role: 'admin');
    $headers['X-Readora-Role'] = 'user';

    $this->withHeaders($headers)->getJson($uri)->assertUnauthorized();
});

it('requires the service token on every endpoint', function () {
    $endpoints = [
        ['GET', '/api/v1/health', ''],
        ['GET', '/api/v1/inventory/books/1', ''],
        ['PUT', '/api/v1/inventory/books/1', '{"stock":1}'],
        ['POST', '/api/v1/reservations', '{"book_id":1}'],
        ['GET', '/api/v1/reservations', ''],
        ['GET', '/api/v1/reservations/1', ''],
        ['DELETE', '/api/v1/reservations/1', ''],
        ['GET', '/api/v1/admin/reservations', ''],
        ['PATCH', '/api/v1/admin/reservations/1/purchase', ''],
    ];

    foreach ($endpoints as [$method, $uri, $body]) {
        foreach (['', 'invalid-token'] as $token) {
            $headers = ['HTTP_ACCEPT' => 'application/json'];

            if ($token !== '') {
                $headers['HTTP_X_SERVICE_TOKEN'] = $token;
            }

            $this->call($method, $uri, server: $headers, content: $body)->assertUnauthorized();
        }
    }
});
