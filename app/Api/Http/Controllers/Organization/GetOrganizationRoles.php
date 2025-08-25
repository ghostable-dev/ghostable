<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Organization;

use App\Api\Resources\Organization\OrganizationRoleResource;
use App\Core\Http\Controllers\Controller;
use App\Organization\Enums\OrganizationRole;

final class GetOrganizationRoles extends Controller
{
    public function __invoke()
    {
        return OrganizationRoleResource::collection(OrganizationRole::cases());
    }
}
