<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\User;

use App\Account\Models\User;
use App\Api\V2\User\Presenters\UserPresenter;
use App\Api\V2\User\Requests\UpdateUserSettingsRequest;
use App\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class UpdateCurrentUser extends Controller
{
    public function __invoke(UpdateUserSettingsRequest $request, UserPresenter $presenter): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        $user->save();

        return response()->json(
            $presenter->present($user->refresh()),
            200
        );
    }
}
