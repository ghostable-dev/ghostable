<?php

namespace App\Environment\Enums;

enum PushMode: string
{
    case ADDITIVE = 'additive';
    case REPLACE = 'replace';

    public function label(): string
    {
        return match ($this) {
            self::ADDITIVE => 'Add / Update Only',
            self::REPLACE => 'Replace Entirely',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ADDITIVE => 'Adds or updates only the keys you paste. Other existing variables are untouched, and no keys are removed.',
            self::REPLACE => 'Makes the pasted keys the entire set for this environment. Keys not in the paste are removed locally and may fall back to inherited values.',
        };
    }
}
