<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireCartIdentifier
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user('sanctum') && !$request->header('X-Cart-Token')) {
            return response()->json([
                'success' => false,
                'message' => 'A cart token or authenticated session is required.',
            ], 401);
        }

        return $next($request);
    }
}
