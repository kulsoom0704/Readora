<?php

use App\Models\User;
use App\Services\InventoryApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.inventory_api.url' => 'http://inventory.test:8001',
        'services.inventory_api.token' => 'test-service-token',
        'services.inventory_api.identity_secret' => 'test-identity-secret',
    ]);

    Http::preventStrayRequests();
});

it('sends service and signed admin identity headers using the API protocol', function () {
    Http::fake(['http://inventory.test:8001/*' => Http::response(['data' => ['book_id' => 47]], 200)]);

    $admin = new User;
    $admin->setAttribute('id', 19);
    $admin->setAttribute('is_admin', true);

    $response = app(InventoryApiClient::class)->request(
        'PUT',
        '/api/v1/inventory/books/47',
        ['stock' => 3],
        $admin,
        'admin-stock-update-47',
    );

    expect($response->successful())->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        $timestamp = (string) $request->header('X-Readora-Timestamp')[0];
        $body = '{"stock":3}';
        $message = implode("\n", [
            'PUT',
            '/api/v1/inventory/books/47',
            $timestamp,
            '19',
            'admin',
            'admin-stock-update-47',
            hash('sha256', $body),
        ]);

        return $request->url() === 'http://inventory.test:8001/api/v1/inventory/books/47'
            && $request->body() === $body
            && $request->hasHeader('X-Service-Token', 'test-service-token')
            && $request->hasHeader('X-Readora-User-Id', '19')
            && $request->hasHeader('X-Readora-Role', 'admin')
            && $request->hasHeader('Idempotency-Key', 'admin-stock-update-47')
            && $request->hasHeader('X-Readora-Signature', hash_hmac('sha256', $message, 'test-identity-secret'));
    });
});

it('uses the configured Inventory API URL', function () {
    config(['services.inventory_api.url' => 'https://inventory.example.test']);
    Http::fake(['https://inventory.example.test/*' => Http::response(['status' => 'ok'])]);

    app(InventoryApiClient::class)->request('GET', '/api/v1/health');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://inventory.example.test/api/v1/health'
        && $request->hasHeader('X-Service-Token', 'test-service-token'));
});

it('preserves pagination query strings while signing the API path only', function () {
    Http::fake(['http://inventory.test:8001/*' => Http::response(['data' => [], 'meta' => ['last_page' => 2]])]);

    $user = new User;
    $user->setAttribute('id', 20);

    app(InventoryApiClient::class)->request('GET', '/api/v1/reservations?page=2', identity: $user);

    Http::assertSent(function (Request $request): bool {
        $timestamp = (string) $request->header('X-Readora-Timestamp')[0];
        $message = implode("\n", [
            'GET',
            '/api/v1/reservations',
            $timestamp,
            '20',
            'user',
            '',
            hash('sha256', ''),
        ]);

        return $request->url() === 'http://inventory.test:8001/api/v1/reservations?page=2'
            && $request->hasHeader('X-Readora-Signature', hash_hmac('sha256', $message, 'test-identity-secret'));
    });
});

it('does not report success when the Inventory API returns an error response', function () {
    Http::fake(['http://inventory.test:8001/*' => Http::response(['message' => 'Unavailable'], 503)]);

    expect(fn () => app(InventoryApiClient::class)->request('GET', '/api/v1/health'))
        ->toThrow(RuntimeException::class, 'Inventory API returned HTTP 503.');
});

it('reports connection failures and timeouts instead of reporting success', function () {
    Http::fake(fn () => throw new ConnectionException('Simulated network timeout.'));

    expect(fn () => app(InventoryApiClient::class)->request('GET', '/api/v1/health'))
        ->toThrow(RuntimeException::class, 'Inventory API is unavailable or timed out.');
});
