<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\User;

use App\Api\V2\User\Presenters\UserPresenter;
use App\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetCurrentUser extends Controller
{
    public function __invoke(Request $request, UserPresenter $presenter): JsonResponse
    {
        $user = $request->user();

        return response()->json(
            $presenter->present($user),
            200
        );
    }
}
