<?php

namespace App\Presentation\Http\Controllers\Discount;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Discount\Services\DiscountService;
use App\Presentation\Http\Requests\Discount\ValidateDiscountRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;

final class ValidateDiscountController
{
    public function __construct(private readonly DiscountService $discounts) {}

    public function __invoke(ValidateDiscountRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');
        $identifier = $user
            ? new CartIdentifier($user->id, null)
            : new CartIdentifier(null, $request->header('X-Cart-Token'));

        try {
            $quote = $this->discounts->preview(
                $identifier,
                $request->validated('code'),
                (float) ($request->validated('shipping_amount') ?? 0),
                $user?->id,
                $request->validated('email'),
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), status: 422);
        }

        unset($quote['discount_code_id'], $quote['customer_key'], $quote['snapshot'], $quote['line_discounts']);
        return ApiResponse::success($quote, 'Discount code applied.');
    }
}
