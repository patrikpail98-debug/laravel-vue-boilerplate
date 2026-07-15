<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestMail;
use App\Models\Setting;
use App\Services\CacheKeyService;
use App\Traits\CacheTrait;
use App\Traits\JsonResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    use CacheTrait, JsonResponseTrait;

    /**
     * Get all settings as a key-value pair object.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');
        return $this->successResponse($settings);
    }

    /**
     * Update multiple settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.auth\.registrations\.enabled' => 'sometimes|boolean',
            'settings.media\.upload\.max_size_kb' => 'sometimes|integer|min:100',
            'settings.org\.name' => 'sometimes|string|max:255',
            'settings.org\.iban' => 'sometimes|string|max:34',
            'settings.org\.bank_name' => 'sometimes|nullable|string|max:255',
            'settings.contact\.address' => 'sometimes|nullable|string|max:255',
            'settings.contact\.phone' => 'sometimes|nullable|string|max:30',
            'settings.contact\.email' => 'sometimes|nullable|string|max:255',
            'settings.contact\.person' => 'sometimes|nullable|string|max:255',
            'settings.contact\.hours' => 'sometimes|nullable|string|max:255',
            // Stored as free-form strings (not `numeric`) so an empty value from the
            // settings form doesn't fail validation - Laravel's `nullable` rule only
            // bypasses other rules for an actual null, not an empty string.
            'settings.contact\.latitude' => 'sometimes|nullable|string|max:50',
            'settings.contact\.longitude' => 'sometimes|nullable|string|max:50',
            'settings.notifications\.sport_email' => 'sometimes|nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(['errors' => $validator->errors()], 422);
        }

        $settings = $request->input('settings');

        foreach ($settings as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Clear any cached settings to ensure changes are applied immediately
        $this->searchAndDelete('*' . CacheKeyService::instance()->getSettingsKey() . '*');

        return $this->successResponse(['message' => 'Settings updated successfully']);
    }

    /**
     * Every raw setting row (key + value), for the "all settings" technical
     * view - unlike index()/update() above, this isn't limited to the
     * curated set of keys the settings form knows about.
     */
    public function allSettings(): JsonResponse
    {
        return $this->successResponse(
            Setting::query()->orderBy('key')->get(['id', 'key', 'value'])
        );
    }

    /**
     * Updates a single setting's raw value by id, from the "all settings" view.
     */
    public function updateOne(Request $request, Setting $setting): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'nullable|string|max:5000',
        ]);

        $setting->update(['value' => $validated['value'] ?? '']);

        $this->searchAndDelete('*' . CacheKeyService::instance()->getSettingsKey() . '*');

        return $this->successResponse($setting);
    }

    /**
     * Get a list of Redis cache keys based on a pattern.
     */
    public function getCacheKeys(Request $request): JsonResponse
    {
        $pattern = $request->input('pattern', '*');
        $prefix = config('cache.prefix', 'laravelapibackend_cache_');

        $keys = Redis::keys($prefix . $pattern);

        // Remove the Laravel prefix for cleaner display on the frontend
        $cleanedKeys = array_map(function ($key) use ($prefix) {
            return str_replace($prefix, '', $key);
        }, $keys);

        return $this->successResponse($cleanedKeys);
    }

    /**
     * Flush Redis cache keys, either all or by a pattern.
     */
    public function flushCache(Request $request): JsonResponse
    {
        $pattern = $request->input('pattern');
        $prefix = config('cache.prefix', 'laravelapibackend_cache_');

        try {
            if ($pattern) {
                // Bind the user-supplied pattern to this app's cache prefix so it
                // can only ever match keys in our own namespace. Without this a
                // bare "*" would wipe the whole Redis instance - sessions, queues
                // and any other app sharing it - not just our cache.
                $this->searchAndDelete($prefix . $pattern);
                $message = "Cache keys matching '{$pattern}' were flushed.";
            } else {
                Artisan::call('cache:clear');
                $message = 'App cache deleted.';
            }
        } catch (Exception $e) {
            return $this->errorResponse(['message' => 'Error when flushing cache: ' . $e->getMessage()], 500);
        }

        return $this->successResponse(['message' => $message]);
    }

    /**
     * Run a whitelisted Artisan command.
     */
    public function runCommand(Request $request): JsonResponse
    {
        $command = $request->json('command');

        // IMPORTANT: Only allow specific, safe commands to be run from the frontend.
        $allowedCommands = [
            'app:update-event-status',
        ];

        if (!in_array($command, $allowedCommands)) {
            return $this->errorResponse(['message' => 'This command is not allowed'], 403);
        }

        try {
            Artisan::call($command);
            $output = Artisan::output();
            return $this->successResponse([
                'message' => "Command '{$command}' successfully executed.",
                'output' => $output
            ]);
        } catch (Exception $e) {
            return $this->errorResponse([
                'message' => "Error when running command '{$command}'.",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            Mail::to($validated['email'])->send(new TestMail());
            return $this->successResponse(['message' => 'Test email sent.']);
        } catch (Exception $e) {
            Log::error('Mail sending failed: ' . $e->getMessage());
            return $this->errorResponse([
                'message' => 'Could not send test email.'
            ], 500);
        }
    }
}
