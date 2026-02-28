<?php

namespace App\Presentation\Http\Controllers\Auth;

use App\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use App\Presentation\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;

final class MeController
{
    public function __invoke(Request $request): UserResource
    {
        $entity = UserMapper::toDomain($request->user());

        return new UserResource($entity);
    }
}
