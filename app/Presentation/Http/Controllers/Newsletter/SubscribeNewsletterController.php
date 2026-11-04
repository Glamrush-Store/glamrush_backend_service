<?php

namespace App\Presentation\Http\Controllers\Newsletter;

use App\Domain\Newsletter\Services\NewsletterSubscriptionService;
use App\Presentation\Http\Requests\Newsletter\SubscribeNewsletterRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class SubscribeNewsletterController
{
    public function __construct(private readonly NewsletterSubscriptionService $service) {}

    public function __invoke(SubscribeNewsletterRequest $request): JsonResponse
    {
        $this->service->subscribe(
            $request->validated('email'),
            $request->validated('source'),
            $request->ip(),
            $request->userAgent(),
        );

        return ApiResponse::success(
            null,
            'If the address can be subscribed, a confirmation email has been sent.',
            202,
        );
    }
}
