<?php

namespace App\Presentation\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ApiResponse
{
    public static function success($data, string $message = 'Success', int $code = 200, ?array $facets = null)
    {
        if ($data instanceof AnonymousResourceCollection) {
            $response = $data->response()->getData(true);

            $body = [
                'success' => true,
                'message' => $message,
                'data'    => $response['data'],
                'meta'    => $response['meta'] ?? null,
                'links'   => $response['links'] ?? null,
            ];

            if ($facets !== null) {
                $body['facets'] = $facets;
            }

            return response()->json($body, $code);
        }

        $body = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if ($facets !== null) {
            $body['facets'] = $facets;
        }

        return response()->json($body, $code);
    }

    public static function error(
        string $message,
        array $errors = [],
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
