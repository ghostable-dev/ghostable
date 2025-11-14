<?php

declare(strict_types=1);

namespace App\Api\Core\Http\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class ApiExceptionMap
{
    public static function register(): void
    {
        /** @var object $handler */
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        $handler->renderable(function (ValidationException $e, $request) {
            if (! str_starts_with($request->path(), 'api/v')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'GHO-VAL-0001',
                    'detail' => $e->getMessage(),
                    'fields' => $e->errors(),
                ],
            ], 422);
        });

        $handler->renderable(function (AuthenticationException $e, $request) {
            if (! str_starts_with($request->path(), 'api/v')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'GHO-AUTH-0001',
                    'detail' => $e->getMessage(),
                ],
            ], 401);
        });

        $handler->renderable(function (AuthorizationException $e, $request) {
            if (! str_starts_with($request->path(), 'api/v')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'GHO-AUTHZ-0001',
                    'detail' => $e->getMessage(),
                ],
            ], 403);
        });

        $handler->renderable(function (ModelNotFoundException $e, $request) {
            if (! str_starts_with($request->path(), 'api/v')) {
                return null;
            }

            return response()->json([
                'error' => [
                    'code' => 'GHO-RES-0001',
                    'detail' => $e->getMessage(),
                ],
            ], 404);
        });
    }
}
