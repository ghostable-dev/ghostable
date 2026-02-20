<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationResource;
use App\Api\V2\Organization\Requests\CreateOrganizationRequest;
use App\Core\Http\Controllers\Controller;
use App\Organization\Actions\CreateOrganization as CreateOrganizationAction;
use Illuminate\Http\JsonResponse;

final class CreateOrganization extends Controller
{
    public function __invoke(CreateOrganizationRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'Authentication required.');

        $organization = app(CreateOrganizationAction::class)->handle(
            name: $request->validated('name'),
            owner: $user
        );

        return response()->json(['data' => new OrganizationResource($organization)], 201);
    }
}
