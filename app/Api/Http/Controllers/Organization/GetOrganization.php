<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Organization;

use App\Api\Resources\Organization\OrganizationResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Models\Organization;
use Illuminate\Http\Request;

final class GetOrganization extends Controller
{
    /**
     * Get the organization resource.
     */
    public function __invoke(Request $request, Organization $organization)
    {
        $this->authorize('view', $organization);

        return new OrganizationResource($organization);
    }
}
