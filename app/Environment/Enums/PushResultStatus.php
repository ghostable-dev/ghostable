<?php

namespace App\Environment\Enums;

enum PushResultStatus: string
{
    case UPDATED = 'updated';
    case UNCHANGED = 'unchanged';

    public function message(): string
    {
        return match ($this) {
            self::UNCHANGED => 'No changes to apply.',
            self::UPDATED => 'Environment updated.',
        };
    }
}
