<?php

declare(strict_types=1);

namespace App\Organization\Casts;

use App\Organization\Entities\FreeOrganizationLimits;
use App\Organization\Entities\GrowthOrganizationLimits;
use App\Organization\Entities\OrganizationLimits;
use App\Organization\Entities\StarterOrganizationLimits;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class OrganizationLimitsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): OrganizationLimits
    {
        if($model->isStarter()) {
            return StarterOrganizationLimits::from(
                json_decode($value ?? '', true) ?? []
            );
        }

        if($model->isGrowth()) {
            return GrowthOrganizationLimits::from(
                json_decode($value ?? '', true) ?? []
            );
        }

        return FreeOrganizationLimits::from(
            json_decode($value ?? '', true) ?? []
        );
    }

    /**
     * Encrypt the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (! $value instanceof OrganizationLimits) {
            throw new InvalidArgumentException('The given value is not a CustomSettings instance.');
        }
 
        return $value->toJson();
    }
}
