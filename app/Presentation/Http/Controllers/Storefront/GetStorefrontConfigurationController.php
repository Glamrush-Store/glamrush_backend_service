<?php

namespace App\Presentation\Http\Controllers\Storefront;

use App\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

final class GetStorefrontConfigurationController
{
    #[OA\Get(
        path: '/api/v1/storefronts/{storefront}/configuration',
        operationId: 'getStorefrontConfiguration',
        summary: 'Get public storefront-wide presentation settings',
        tags: ['Storefront'],
        parameters: [
            new OA\Parameter(
                name: 'storefront',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', example: 'fragrances'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Public storefront presentation settings.',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'message' => 'Success',
                    'data' => [
                        'announcement' => [
                            'primary_text' => 'Free Lagos delivery on orders over ₦100,000',
                            'secondary_text' => 'Complimentary scent consultation',
                        ],
                    ],
                ]),
            ),
            new OA\Response(response: 404, description: 'Unknown or inactive storefront.'),
        ],
    )]
    public function __invoke(string $storefront): JsonResponse
    {
        $category = Category::query()
            ->where('slug', $storefront)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->firstOrFail(['announcement_primary_text', 'announcement_secondary_text']);

        return ApiResponse::success([
            'announcement' => [
                'primary_text' => $category->announcement_primary_text,
                'secondary_text' => $category->announcement_secondary_text,
            ],
        ]);
    }
}
