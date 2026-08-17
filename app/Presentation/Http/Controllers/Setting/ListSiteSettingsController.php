<?php

namespace App\Presentation\Http\Controllers\Setting;

use App\Domain\Setting\Services\SiteSettingService;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListSiteSettingsController
{
    public function __construct(private readonly SiteSettingService $settings) {}

    public function __invoke(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $key = $request->query('key');

        return ApiResponse::success($this->settings->get(
            is_string($category) ? $category : null,
            is_string($key) ? $key : null,
        ));
    }
}
