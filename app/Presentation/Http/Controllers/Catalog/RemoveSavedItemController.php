<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\SavedItem\Services\SavedItemService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class RemoveSavedItemController
{
    public function __construct(
        private readonly SavedItemService $service,
    ) {}

    public function __invoke(Request $request, string $productId): Response
    {
        $this->service->remove($request->user()->id, $productId);

        return response()->noContent();
    }
}
