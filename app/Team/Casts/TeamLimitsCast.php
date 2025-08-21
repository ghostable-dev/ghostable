<?php

namespace App\Team\Casts;

use App\Team\Entities\OrgTeamLimits;
use App\Team\Entities\PersonalTeamLimits;
use App\Team\Entities\TeamLimits;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class TeamLimitsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): TeamLimits
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

        $class = $kind === 'personal' ? PersonalTeamLimits::class : OrgTeamLimits::class;

        return $class::fromArray($data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof TeamLimits) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
