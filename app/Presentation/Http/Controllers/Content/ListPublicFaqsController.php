<?php

namespace App\Presentation\Http\Controllers\Content;

use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Domain\Content\Services\PublicContentService;
use App\Presentation\Http\Requests\Content\ListPublicFaqsRequest;
use Illuminate\Http\JsonResponse;

final class ListPublicFaqsController
{
    public function __construct(
        private readonly PublicContentService $content,
        private readonly StorefrontContext $storefront,
    ) {}

    public function __invoke(ListPublicFaqsRequest $request): JsonResponse
    {
        $paginator = $this->content->paginateFaqs(
            rootCategoryId: (string) $this->storefront->rootCategoryId(),
            category: $request->validated('category'),
            search: $request->validated('search'),
            perPage: (int) ($request->validated('per_page') ?? 20),
            page: (int) ($request->validated('page') ?? 1),
        );

        $groups = $paginator->getCollection()
            ->groupBy('faq_category_id')
            ->map(function ($faqs) {
                $category = $faqs->first()->category;

                return [
                    'id' => (string) $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'faqs' => $faqs->map(fn ($faq) => [
                        'id' => (string) $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ])->values()->all(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data' => $groups,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
