<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    use JsonResponseTrait;

    public function index(): JsonResponse
    {
        return $this->successResponse(Area::query()->with('playgrounds')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
        ]);

        $area = Area::query()->create($validated);

        return $this->successResponse($area, 201);
    }

    public function update(Request $request, Area $area): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string|max:255',
        ]);

        $area->update($validated);

        return $this->successResponse($area);
    }

    public function destroy(Area $area): JsonResponse
    {
        $area->delete();

        return $this->successResponse(['message' => 'Areál bol vymazaný.']);
    }
}
