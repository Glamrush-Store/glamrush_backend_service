<?php

namespace App\Presentation\Http\Middleware;

use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Infrastructure\Caching\CacheTags;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Models\Category;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveStorefrontCategory
{
    public function __construct(
        private readonly StorefrontContext $context,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('storefront');

        $storefront = QueryCache::rememberTagged(
            "storefront:context:{$slug}:v1",
            [CacheTags::STOREFRONTS, CacheTags::CATEGORIES],
            (int) config('api_cache.storefront_context_ttl', 300),
            fn (): ?array => $this->resolve($slug),
        );

        if (! $storefront) {
            return response()->json([
                'success' => false,
                'message' => 'Storefront not found.',
                'data' => null,
                'errors' => [],
            ], 404);
        }

        $this->context->activate($storefront['slug'], $storefront['category_ids']);

        return $next($request);
    }

    /** @return array{slug: string, category_ids: list<string>}|null */
    private function resolve(string $slug): ?array
    {
        $root = Category::query()
            ->where('slug', $slug)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with('childrenRecursive')
            ->first();

        if (! $root) {
            return null;
        }

        $categoryIds = [$root->id];
        $this->collectActiveDescendantIds($root, $categoryIds);

        return ['slug' => $root->slug, 'category_ids' => $categoryIds];
    }

    /** @param list<string> $categoryIds */
    private function collectActiveDescendantIds(Category $category, array &$categoryIds): void
    {
        foreach ($category->childrenRecursive as $child) {
            if (! $child->is_active) {
                continue;
            }

            $categoryIds[] = $child->id;
            $this->collectActiveDescendantIds($child, $categoryIds);
        }
    }
}
