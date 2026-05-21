<?php

namespace App\Presentation\Http\Controllers\Order;

use App\Domain\Order\Services\OrderService;
use App\Presentation\Http\Resources\Order\OrderResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\Request;

final class ListMyOrdersController
{
    public function __construct(
        private readonly OrderService $orders,
    ) {
    }

    public function __invoke(Request $request)
    {
        $orders = $this->orders->paginateForUser(
            userId: (string)$request->user()->id,
            perPage: $request->integer('per_page', 15),
            page: $request->integer('page', 1),
        );

        return ApiResponse::success(OrderResource::collection($orders));
    }
}
