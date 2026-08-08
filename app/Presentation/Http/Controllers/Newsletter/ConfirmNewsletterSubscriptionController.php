<?php

namespace App\Presentation\Http\Controllers\Newsletter;

use App\Domain\Newsletter\Services\NewsletterSubscriptionService;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ConfirmNewsletterSubscriptionController
{
    public function __construct(private readonly NewsletterSubscriptionService $service) {}

    public function __invoke(string $token): JsonResponse
    {
        $this->service->confirm($token);

        return ApiResponse::success(
            ['status' => 'subscribed'],
            'Your newsletter subscription has been confirmed.',
        );
    }
}
