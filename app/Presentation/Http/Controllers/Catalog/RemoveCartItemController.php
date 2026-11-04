<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Exceptions\AmbiguousCartItemException;
use App\Domain\Catalog\Cart\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RemoveCartItemController
{
    public function __construct(
        private readonly CartService $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $id = $this->resolveIdentifier($request);

        try {
            $this->service->remove($id, (string) $request->route('productId'));
        } catch (AmbiguousCartItemException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 409);
        }

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
