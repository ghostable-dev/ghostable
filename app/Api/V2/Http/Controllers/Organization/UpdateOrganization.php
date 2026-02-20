<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationResource;
use App\Api\V2\Organization\Requests\UpdateOrganizationSettingsRequest;
use App\Core\Http\Controllers\Controller;
use App\Organization\Actions\UpdateOrganizationSettings;
use App\Organization\Models\Organization;
use Illuminate\Support\Arr;

final class UpdateOrganization extends Controller
{
    public function __invoke(UpdateOrganizationSettingsRequest $request, Organization $organization)
    {
        $this->authorize('manageSettings', $organization);

        $payload = Arr::only($request->validated(), [
            'name',
        ]);

        $organization = app(UpdateOrganizationSettings::class)->handle($organization, $payload);

        return new OrganizationResource($organization->refresh());
    }
}
