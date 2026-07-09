<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Playground;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

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
        if ($playground->image_path) {
            Storage::disk('public')->delete($playground->image_path);
        }

        $playground->delete();

        return $this->successResponse(['message' => 'Ihrisko bolo vymazané.']);
    }

    public function uploadImage(Request $request, Playground $playground): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        if ($playground->image_path) {
            Storage::disk('public')->delete($playground->image_path);
        }

        $path = $validated['image']->store('playgrounds', 'public');

        ImageOptimizer::optimize(storage_path('app/public/' . $path));

        $playground->update(['image_path' => $path]);

        return $this->successResponse($playground);
    }

    public function deleteImage(Playground $playground): JsonResponse
    {
        if ($playground->image_path) {
            Storage::disk('public')->delete($playground->image_path);
            $playground->update(['image_path' => null]);
        }

        return $this->successResponse($playground);
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
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);
    }
}
