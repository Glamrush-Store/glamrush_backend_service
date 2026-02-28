<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Services\PasswordService;
use App\Presentation\Http\Requests\Auth\VerifyPasswordCodeRequest;
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

        return response()->json(['message' => 'Code verified successfully.']);
    }
}
