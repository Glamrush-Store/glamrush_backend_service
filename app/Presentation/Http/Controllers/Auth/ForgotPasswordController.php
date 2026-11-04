<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Services\PasswordService;
use App\Presentation\Http\Requests\Auth\ForgotPasswordRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ForgotPasswordController
{
    public function __construct(private readonly PasswordService $passwordService) {}

    public function __invoke(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordService->sendResetCode($request->validated('email'));

        return ApiResponse::success(null, 'If this email exists, a reset code has been sent.');
    }
}
