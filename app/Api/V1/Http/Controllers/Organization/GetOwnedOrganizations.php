<?php

declare(strict_types=1);

namespace App\Api\V1\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationResource;
use Illuminate\Http\Request;

final class GetOwnedOrganizations
{
    /**
     * Get the authenticated users "owned" organizations.
     */
    public function __invoke(Request $request)
    {
        $organizations = $request->user()->ownedOrganizations;

        return OrganizationResource::collection($organizations);
    }
}
