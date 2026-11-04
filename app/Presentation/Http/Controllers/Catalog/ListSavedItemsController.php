<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\SavedItem\Services\SavedItemService;
use App\Presentation\Http\Resources\Catalog\SavedItemResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListSavedItemsController
{
    public function __construct(
        private readonly SavedItemService $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $items = $this->service->list($request->user()->id);

        return ApiResponse::success(SavedItemResource::collection($items));
    }
}
