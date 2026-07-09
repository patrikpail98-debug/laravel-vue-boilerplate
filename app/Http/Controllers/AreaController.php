<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;

class AreaController extends Controller
{
    use JsonResponseTrait;

    /**
     * Public catalog of areas with their active playgrounds.
     */
    public function index(): JsonResponse
    {
        $areas = Area::query()
            ->with(['playgrounds' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return $this->successResponse($areas);
    }

    public function show(Area $area): JsonResponse
    {
        $area->load(['playgrounds' => function ($query) {
            $query->where('is_active', true);
        }]);

        return $this->successResponse($area);
    }
}
