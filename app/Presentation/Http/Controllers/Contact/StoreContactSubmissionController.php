<?php

namespace App\Presentation\Http\Controllers\Contact;

use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Domain\Contact\Services\ContactSubmissionService;
use App\Presentation\Http\Requests\Contact\StoreContactSubmissionRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class StoreContactSubmissionController
{
    public function __construct(
        private readonly ContactSubmissionService $contacts,
        private readonly StorefrontContext $storefront,
    ) {}

    public function __invoke(StoreContactSubmissionRequest $request): JsonResponse
    {
        $reference = $this->contacts->submit(
            storefrontCategoryId: (string) $this->storefront->rootCategoryId(),
            customerAccountId: $request->user('sanctum')?->id,
            data: $request->validated(),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return ApiResponse::success(
            ['reference' => $reference],
            'Your message has been received.',
            202,
        );
    }
}
