<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;

class PublicSettingsController extends Controller
{
    use JsonResponseTrait;

    /**
     * Whitelisted settings safe to expose without authentication
     * (used by the public footer/contact page).
     */
    public function index(): JsonResponse
    {
        $settings = Setting::query()
            ->whereIn('key', Setting::PUBLIC_KEYS)
            ->pluck('value', 'key');

        return $this->successResponse($settings);
    }
}
