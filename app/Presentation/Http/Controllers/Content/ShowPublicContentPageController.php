<?php

namespace App\Presentation\Http\Controllers\Content;

use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Domain\Content\Services\PublicContentService;
use App\Presentation\Http\Resources\Content\PublicContentPageResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ShowPublicContentPageController
{
    public function __construct(
        private readonly PublicContentService $content,
        private readonly StorefrontContext $storefront,
    ) {}

    public function __invoke(string $storefront, string $slug): JsonResponse
    {
        $page = $this->content->findPage($slug, (string) $this->storefront->rootCategoryId());

        return $page
            ? ApiResponse::success(new PublicContentPageResource($page))
            : ApiResponse::error('Page not found.', status: 404);
    }
}
