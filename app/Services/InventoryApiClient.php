<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;

class InventoryApiClient
{
    private const TIMEOUT_SECONDS = 10;

    private const CONNECT_TIMEOUT_SECONDS = 3;

    /**
     * Send a request to the Inventory API, optionally asserting a Readora user identity.
     *
     * @param  array<string, mixed>|null  $payload
     *
     * @throws JsonException
     */
    public function request(
        string $method,
        string $path,
        ?array $payload = null,
        ?User $identity = null,
        string $idempotencyKey = '',
    ): Response {
        $baseUrl = rtrim((string) config('services.inventory_api.url', ''), '/');
        $serviceToken = (string) config('services.inventory_api.token', '');
        $identitySecret = (string) config('services.inventory_api.identity_secret', '');

        if ($baseUrl === '' || $serviceToken === '') {
            throw new InventoryApiException('Inventory API URL and service token must be configured.');
        }

        $requestPath = parse_url($path, PHP_URL_PATH);
        $query = parse_url($path, PHP_URL_QUERY);

        if (
            ! is_string($requestPath)
            || ! str_starts_with($requestPath, '/')
            || parse_url($path, PHP_URL_HOST) !== null
            || parse_url($path, PHP_URL_FRAGMENT) !== null
        ) {
            throw new InventoryApiException('Inventory API paths must be relative paths beginning with /.');
        }

        $requestUri = $requestPath.($query === null ? '' : '?'.$query);

        $body = $payload === null
            ? ''
            : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $headers = ['X-Service-Token' => $serviceToken];

        if ($idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        if ($identity !== null) {
            if ($identitySecret === '') {
                throw new InventoryApiException('Inventory API identity signing secret must be configured.');
            }

            $timestamp = (string) now()->timestamp;
            $userId = (string) $identity->getKey();
            $role = $identity->is_admin ? 'admin' : 'user';
            $message = implode("\n", [
                strtoupper($method),
                $requestPath,
                $timestamp,
                $userId,
                $role,
                $idempotencyKey,
                hash('sha256', $body),
            ]);

            $headers += [
                'X-Readora-User-Id' => $userId,
                'X-Readora-Role' => $role,
                'X-Readora-Timestamp' => $timestamp,
                'X-Readora-Signature' => hash_hmac('sha256', $message, $identitySecret),
            ];
        }

        try {
            $request = Http::acceptJson()
                ->withHeaders($headers)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS);

            if ($payload !== null) {
                $request->withBody($body, 'application/json');
            }

            $response = $request->send(strtoupper($method), $baseUrl.$requestUri);
        } catch (ConnectionException $exception) {
            throw new InventoryApiException('Inventory API is unavailable or timed out.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new InventoryApiException('Inventory API returned HTTP '.$response->status().'.', $response->status());
        }

        return $response;
    }
}
