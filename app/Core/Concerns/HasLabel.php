<?php

namespace App\Core\Concerns;

trait HasLabel
{
    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    abstract public function label(): string;
}
