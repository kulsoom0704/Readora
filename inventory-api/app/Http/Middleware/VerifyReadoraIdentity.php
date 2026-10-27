<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyReadoraIdentity
{
    private const MAXIMUM_CLOCK_SKEW_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $userId = (string) $request->header('X-Readora-User-Id', '');
        $role = (string) $request->header('X-Readora-Role', '');
        $timestamp = (string) $request->header('X-Readora-Timestamp', '');
        $providedSignature = (string) $request->header('X-Readora-Signature', '');
        $idempotencyKey = (string) $request->header('Idempotency-Key', '');
        $secret = (string) config('services.inventory_api.identity_secret', '');

        if (
            $secret === ''
            || ! ctype_digit($userId)
            || (int) $userId < 1
            || ! in_array($role, ['user', 'admin'], true)
            || ! ctype_digit($timestamp)
            || abs(now()->timestamp - (int) $timestamp) > self::MAXIMUM_CLOCK_SKEW_SECONDS
            || $providedSignature === ''
        ) {
            return $this->unauthorized();
        }

        $message = implode("\n", [
            strtoupper($request->method()),
            $request->getPathInfo(),
            $timestamp,
            $userId,
            $role,
            $idempotencyKey,
            hash('sha256', $request->getContent()),
        ]);
        $expectedSignature = hash_hmac('sha256', $message, $secret);

        if (! hash_equals($expectedSignature, $providedSignature)) {
            return $this->unauthorized();
        }

        $request->attributes->set('readora_user_id', (int) $userId);
        $request->attributes->set('readora_role', $role);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json(['message' => 'A valid Readora identity assertion is required.'], Response::HTTP_UNAUTHORIZED);
    }
}
