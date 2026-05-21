<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\SavedItem\Services\SavedItemService;
use App\Presentation\Http\Requests\SavedItem\AddSavedItemRequest;
use App\Presentation\Http\Resources\Catalog\SavedItemResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AddSavedItemController
{
    public function __construct(
        private readonly SavedItemService $service,
    ) {}

    public function __invoke(AddSavedItemRequest $request): JsonResponse
    {
        $result = $this->service->add(
            $request->user()->id,
            $request->validated('product_id'),
        );

        $status = $result['created'] ? 201 : 200;
        $message = $result['created'] ? 'Item saved.' : 'Item already saved.';

        return ApiResponse::success(new SavedItemResource($result['entity']), $message, $status);
    }
}
