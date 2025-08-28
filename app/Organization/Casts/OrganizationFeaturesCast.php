<?php

declare(strict_types=1);

namespace App\Organization\Casts;

use App\Organization\Entities\OrganizationFeatures;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class OrganizationFeaturesCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): OrganizationFeatures
    {
        $overrides = json_decode($value ?? '', true) ?? [];

        return OrganizationFeatures::fromPlan($model->plan)->withOverrides($overrides);
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (! $value instanceof OrganizationFeatures) {
            throw new InvalidArgumentException('The given value is not a OrganizationFeatures instance.');
        }

        return $value->toJson();
    }
}
