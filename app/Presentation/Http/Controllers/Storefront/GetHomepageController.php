<?php

namespace App\Presentation\Http\Controllers\Storefront;

use App\Domain\Storefront\Services\StorefrontHomepageService;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class GetHomepageController
{
    public function __construct(
        private readonly StorefrontHomepageService $homepage,
    ) {
    }

    #[OA\Get(
        path: '/api/v1/storefronts/{storefront}/homepage',
        operationId: 'getStorefrontHomepage',
        summary: 'Get the fully hydrated public storefront homepage',
        tags: ['Storefront'],
        parameters: [
            new OA\Parameter(
                name: 'storefront',
                description: 'Active root-category storefront slug',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'fragrances'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Published homepage content. Campaign is nullable and sections may be empty.',
                content: new OA\JsonContent(
                    example: [
                        'success' => true,
                        'message' => 'Success',
                        'data' => [
                            'storefront' => ['slug' => 'fragrances', 'name' => 'Fragrances'],
                            'campaign' => [
                                'id' => '01K1ABCDEF1234567890ABCDEF',
                                'eyebrow' => 'After-dark fragrances',
                                'title' => 'Leave a trace.',
                                'description' => 'A magnetic collection for after dark.',
                                'desktop_image' => 'https://cdn.example.com/campaign-desktop.webp',
                                'mobile_image' => 'https://cdn.example.com/campaign-mobile.webp',
                                'cta_label' => 'Shop the campaign',
                                'cta_url' => '/collections/midnight-edit',
                                'starts_at' => '2026-08-01T00:00:00.000000Z',
                                'ends_at' => null,
                            ],
                            'sections' => [
                                [
                                    'id' => '01K1ABCDEF1234567890ABCDEG',
                                    'type' => 'featured_products',
                                    'title' => 'Currently coveted',
                                    'subtitle' => null,
                                    'display_order' => 1,
                                    'items' => [],
                                ]
                            ],
                        ],
                    ],
                ),
            ),
            new OA\Response(response: 404, description: 'Unknown, inactive, or non-root storefront'),
        ],
    )]
    public function __invoke(string $storefront): JsonResponse
    {
        return ApiResponse::success($this->homepage->get($storefront));
    }
}
