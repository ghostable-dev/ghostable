<?php

namespace App\Team\Casts;

use App\Team\Entities\OrgTeamFeatures;
use App\Team\Entities\PersonalTeamFeatures;
use App\Team\Entities\TeamFeatures;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class TeamFeaturesCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): TeamFeatures
    {
        $data = [];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        } elseif (is_array($value)) {
            $data = $value;
        }

        $kind = $data['kind'] ?? ($model->isPersonal() ? 'personal' : 'org');
        unset($data['kind']);

        $class = $kind === 'personal' ? PersonalTeamFeatures::class : OrgTeamFeatures::class;

        return $class::fromArray($data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof TeamFeatures) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
