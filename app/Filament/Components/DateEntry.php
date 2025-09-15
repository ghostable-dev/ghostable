<?php

namespace App\Filament\Components;

use Filament\Infolists\Components\TextEntry;

class DateEntry extends TextEntry
{
    public static function make(?string $name = null): static
    {
        $static = parent::make($name);

        $static->placeholder('N/A');

        $static->formatStateUsing(fn ($state) => $state->timezone(timezone())?->format(DT_FORMAT));

        return $static;
    }
}
