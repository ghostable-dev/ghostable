<?php

declare(strict_types=1);

namespace App\Integration\Casts;

use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationSettingsRegistry;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Spatie\LaravelData\Data;

class IntegrationSettingsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        $settings = $this->decodeSettings($value);

        $dataClass = $this->resolveSettingsType($model);

        if ($dataClass && is_subclass_of($dataClass, Data::class)) {
            return $dataClass::from($settings);
        }

        return $settings;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof Data) {
            return $value->toJson();
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        throw new InvalidArgumentException('Integration settings must be an array or Data instance.');
    }

    /**
     * Decode the stored settings value back to an array.
     */
    protected function decodeSettings(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function resolveSettingsType(Model $model): ?string
    {
        if (! $model instanceof Integration) {
            return null;
        }

        return IntegrationSettingsRegistry::resolve($model->key);
    }
}
