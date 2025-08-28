<?php

declare(strict_types=1);

namespace App\Organization\Casts;

use App\Organization\Entities\OrganizationLimits;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class OrganizationLimitsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): OrganizationLimits
    {
        $overrides = json_decode($value ?? '', true) ?? [];

        return OrganizationLimits::fromPlan($model->plan)->withOverrides($overrides);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (! $value instanceof OrganizationLimits) {
            throw new InvalidArgumentException('The given value is not a OrganizationLimits instance.');
        }

        return $value->toJson();
    }
}
