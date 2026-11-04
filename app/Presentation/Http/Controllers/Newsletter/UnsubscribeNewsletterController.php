<?php

namespace App\Presentation\Http\Controllers\Newsletter;

use App\Domain\Newsletter\Services\NewsletterSubscriptionService;
use App\Presentation\Http\Requests\Newsletter\UnsubscribeNewsletterRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class UnsubscribeNewsletterController
{
    public function __construct(private readonly NewsletterSubscriptionService $service) {}

    public function __invoke(UnsubscribeNewsletterRequest $request): JsonResponse
    {
        $this->service->unsubscribe($request->validated('token'));

        return ApiResponse::success(
            ['status' => 'unsubscribed'],
            'You have been unsubscribed from the newsletter.',
        );
    }
}
