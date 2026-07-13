<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Every api/* request is JSON-only (no HTML fallback exists for it), so
     * always render exceptions as JSON there - regardless of the request's
     * Accept header - letting the parent handler's normal status-code mapping
     * apply (422 + field errors for ValidationException, 404 for a missing
     * model/route, 401 for AuthenticationException, etc.) instead of masking
     * everything as a generic 500.
     */
    protected function shouldReturnJson($request, Throwable $e): bool
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }
}

