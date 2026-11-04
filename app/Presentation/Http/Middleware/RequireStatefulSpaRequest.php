<?php

namespace App\Presentation\Http\Middleware;

use App\Presentation\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

final class RequireStatefulSpaRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->attributes->get('sanctum') || ! $request->hasSession()) {
            return ApiResponse::error(
                'This authentication endpoint requires a stateful SPA request.',
                [],
                400,
            );
        }

        return $next($request);
    }
}
