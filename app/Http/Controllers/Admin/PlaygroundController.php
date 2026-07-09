<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Playground;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaygroundController extends Controller
{
    use JsonResponseTrait;

    public function index(): JsonResponse
    {
        return $this->successResponse(Playground::query()->with('area')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);

        $playground = Playground::query()->create($validated);

        return $this->successResponse($playground, 201);
    }

    public function update(Request $request, Playground $playground): JsonResponse
    {
        $validated = $this->validated($request);

        $playground->update($validated);

        return $this->successResponse($playground);
    }

    public function destroy(Playground $playground): JsonResponse
    {
        $playground->delete();

        return $this->successResponse(['message' => 'Ihrisko bolo vymazané.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'area_id' => 'required|exists:areas,id',
            'name' => 'required|string|max:255',
            'price_per_30min' => 'required|numeric|min:0',
            'max_horizon_days' => 'required|integer|min:1|max:365',
            'max_duration_minutes' => 'required|integer|min:30|max:1440',
            'is_active' => 'required|boolean',
        ]);
    }
}
