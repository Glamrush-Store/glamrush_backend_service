<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Services\AuthService;
use App\Presentation\Http\Resources\Auth\UserResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class SocialCallbackController
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(Request $request, string $provider): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $result = $this->authService->handleSocialAuth($provider, $request->input('token'));
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'token' => ['The provided social token is invalid.'],
            ]);
        }

        return ApiResponse::success([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ], 'Authenticated successfully.', 201);
    }
}
