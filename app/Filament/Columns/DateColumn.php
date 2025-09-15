<?php

namespace App\Filament\Columns;

use Filament\Tables\Columns\TextColumn;

class DateColumn extends TextColumn
{
    public static function make(?string $name = null): static
    {
        $static = parent::make($name);

        $static->formatStateUsing(fn ($state) => $state->timezone(timezone())->format(DT_FORMAT));

        return $static;
    }
}
