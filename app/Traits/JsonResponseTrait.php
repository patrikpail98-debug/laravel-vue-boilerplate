<?php
namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait JsonResponseTrait
{
    /**
     * @param $success
     * @param $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function jsonResponse($success, $message, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
        ];

        return response()->json($response, $statusCode);
    }

    /**
     * @param $data
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data, int $statusCode = 200): JsonResponse
    {
        return response()->json($data, $statusCode);
    }

    /**
     * 400 (generic client error) by default - callers dealing with a specific
     * error class (auth, validation, not-found, ...) should always pass an
     * explicit status code rather than relying on this default.
     */
    protected function errorResponse($message, int $statusCode = 400): JsonResponse
    {
        return response()->json($message, $statusCode);
    }
}
