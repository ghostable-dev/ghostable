<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Organization;

use App\Api\Resources\Organization\OrganizationResource;
use Illuminate\Http\Request;

final class GetOrganizations
{
    /**
     * Get the authenticated users "member" organizations.
     */
    public function __invoke(Request $request)
    {
        $organizations = $request->user()->organizations;

        return OrganizationResource::collection($organizations);
    }
}
