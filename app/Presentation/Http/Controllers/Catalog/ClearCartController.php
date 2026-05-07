<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ClearCartController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $id = $this->resolveIdentifier($request);

        $this->service->clear($id);

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
