<?php

namespace App\Presentation\Http\Controllers\Newsletter;

use App\Domain\Newsletter\Services\NewsletterSubscriptionService;
use App\Presentation\Http\Requests\Newsletter\ResendNewsletterConfirmationRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ResendNewsletterConfirmationController
{
    public function __construct(private readonly NewsletterSubscriptionService $service) {}

    public function __invoke(ResendNewsletterConfirmationRequest $request): JsonResponse
    {
        $this->service->resendConfirmation($request->validated('email'));

        return ApiResponse::success(
            null,
            'If the subscription is awaiting confirmation, a new email has been sent.',
            202,
        );
    }
}
