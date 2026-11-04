<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Services\AuthService;
use App\Presentation\Http\Requests\Auth\RegisterRequest;
use App\Presentation\Http\Resources\Auth\UserResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class RegisterController
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        Auth::guard('web')->login($result['authenticatable']);
        $request->session()->regenerate();

        return ApiResponse::success([
            'user' => new UserResource($result['user']),
        ], 'Registered successfully.', 201);
    }
}
