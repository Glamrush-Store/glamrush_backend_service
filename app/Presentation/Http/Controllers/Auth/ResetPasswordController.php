<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Services\PasswordService;
use App\Presentation\Http\Requests\Auth\ResetPasswordRequest;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ResetPasswordController
{
    public function __construct(private readonly PasswordService $passwordService) {}

    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordService->resetPassword(
            $request->validated('email'),
            $request->validated('password'),
        );

        return ApiResponse::success(null, 'Password has been reset successfully.');
    }
}
