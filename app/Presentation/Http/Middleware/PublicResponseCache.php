<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PublicResponseCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodCacheable() || $response->getStatusCode() !== Response::HTTP_OK) {
            return $response;
        }

        $maxAge = max(0, (int) config('api_cache.http.max_age', 60));
        $sharedMaxAge = max($maxAge, (int) config('api_cache.http.shared_max_age', 60));
        $staleWhileRevalidate = max(0, (int) config('api_cache.http.stale_while_revalidate', 15));

        $response->headers->set('Cache-Control', implode(', ', [
            'public',
            "max-age={$maxAge}",
            "s-maxage={$sharedMaxAge}",
            "stale-while-revalidate={$staleWhileRevalidate}",
        ]));
        $response->setVary(['Accept', 'Accept-Encoding']);
        $response->setEtag(hash('sha256', (string) $response->getContent()));

        $response->isNotModified($request);

        return $response;
    }
}
