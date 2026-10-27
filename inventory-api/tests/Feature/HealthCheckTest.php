<?php

test('a valid service token can access the health check', function () {
    config()->set('services.inventory_api.token', 'local-test-token');

    $this->withHeader('X-Service-Token', 'local-test-token')
        ->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});

test('a missing service token is unauthorized', function () {
    config()->set('services.inventory_api.token', 'local-test-token');

    $this->getJson('/api/v1/health')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthorized.']);
});

test('an invalid service token is unauthorized', function () {
    config()->set('services.inventory_api.token', 'local-test-token');

    $this->withHeader('X-Service-Token', 'wrong-token')
        ->getJson('/api/v1/health')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthorized.']);
});
