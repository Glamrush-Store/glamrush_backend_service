<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RemoveCartItemController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function __invoke(Request $request, string $productId): JsonResponse
    {
        $id = $this->resolveIdentifier($request);

        $this->service->remove($id, $productId);

        return response()->json(null, 204);
    }

    private function resolveIdentifier(Request $request): CartIdentifier
    {
        if ($user = $request->user('sanctum')) {
            return new CartIdentifier($user->id, null);
        }

        return new CartIdentifier(null, $request->header('X-Cart-Token'));
    }
}
