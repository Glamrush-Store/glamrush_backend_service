<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\SavedItem\Services\SavedItemService;
use App\Presentation\Http\Requests\SavedItem\SyncSavedItemsRequest;
use App\Presentation\Http\Resources\Catalog\SavedItemResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class SyncSavedItemsController
{
    public function __construct(
        private readonly SavedItemService $service,
    ) {}

    public function __invoke(SyncSavedItemsRequest $request): JsonResponse
    {
        $items = $this->service->sync(
            $request->user()->id,
            $request->validated('product_ids'),
        );

        return ApiResponse::success(SavedItemResource::collection($items));
    }
}
