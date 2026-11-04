<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key'));

        if (! preg_match('/^[A-Za-z0-9._:-]{16,100}$/', $key)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'A valid Idempotency-Key header is required.',
                'data' => null,
                'errors' => [
                    'Idempotency-Key' => ['Use 16 to 100 letters, numbers, dots, underscores, colons, or hyphens.'],
                ],
            ], 422);
        }

        $request->attributes->set('idempotency_key', $key);

        return $next($request);
    }
}
