<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireReadoraAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('readora_role') !== 'admin') {
            return response()->json(['message' => 'Administrator access is required.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
