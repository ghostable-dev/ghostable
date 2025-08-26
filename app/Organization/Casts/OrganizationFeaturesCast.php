<?php

declare(strict_types=1);

namespace App\Organization\Casts;

use App\Organization\Entities\FreeOrganizationFeatures;
use App\Organization\Entities\GrowthOrganizationFeatures;
use App\Organization\Entities\OrganizationFeatures;
use App\Organization\Entities\StarterOrganizationFeatures;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class OrganizationFeaturesCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): OrganizationFeatures
    {
        $data = json_decode($value ?? '', true) ?? [];

        if ($model->isStarter()) {
            return StarterOrganizationFeatures::fromArray($data);
        }

        if ($model->isGrowth()) {
            return GrowthOrganizationFeatures::fromArray($data);
        }

        return FreeOrganizationFeatures::fromArray($data);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof OrganizationFeatures) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        throw new InvalidArgumentException('The given value is not a OrganizationFeatures instance.');
    }
}
