<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use App\Presentation\Http\Resources\Auth\UserResource;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        $entity = UserMapper::toDomain($request->user());

        return ApiResponse::success(new UserResource($entity));
    }
}
