<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Organization;

use App\Api\Core\Resources\Organization\OrganizationResource;
use Illuminate\Http\Request;

final class GetOwnedOrganizations
{
    /**
     * Get the organizations the authenticated user owns.
     */
    public function __invoke(Request $request)
    {
        $organizations = $request->user()
            ->ownedOrganizations()
            ->where('desktop_licensing_enabled', false)
            ->get();

        return OrganizationResource::collection($organizations);
    }
}
