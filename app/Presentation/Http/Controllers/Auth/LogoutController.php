<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Domain\User\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class LogoutController
{
    public function __construct(private readonly AuthService $authService) {}

    public function __invoke(Request $request): Response
    {
        $this->authService->logout($request->user());

        return response()->noContent();
    }
}
