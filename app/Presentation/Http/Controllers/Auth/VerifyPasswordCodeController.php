<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Services\PasswordService;
use App\Presentation\Http\Requests\Auth\VerifyPasswordCodeRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class VerifyPasswordCodeController
{
    public function __construct(private readonly PasswordService $passwordService) {}

    public function __invoke(VerifyPasswordCodeRequest $request): JsonResponse
    {
        $this->passwordService->verifyCode(
            $request->validated('email'),
            $request->validated('code'),
        );

        return ApiResponse::success(null, 'Code verified successfully.');
    }
}
