<?php

namespace App\Environment\Enums;

enum EnvFileFormat: string
{
    case ALPHABETICAL = 'alphabetical';
    case GROUPED = 'grouped';
    case GROUPED_COMMENTS = 'grouped:comments';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $fmt) => [$fmt->value => $fmt->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::ALPHABETICAL => 'Alphabetical',
            self::GROUPED => 'Grouped',
            self::GROUPED_COMMENTS => 'Grouped (with Comments)',
        };
    }
}
