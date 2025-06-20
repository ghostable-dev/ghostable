<?php

namespace App\Environment\Enums;

enum EnvironmentVariableRuleType: string
{
    case STRING = 'string';
    case BOOLEAN = 'boolean';
    case INTEGER = 'integer';
    case EMAIL = 'email';
    case URL = 'url';
    case ENUM = 'enum'; // maps to "in" rule
    case REGEX = 'regex';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::STRING => 'Text',
            self::BOOLEAN => 'True / False',
            self::INTEGER => 'Number',
            self::EMAIL => 'Email',
            self::URL => 'URL',
            self::ENUM => 'Select from list',
            self::REGEX => 'Pattern (Regex)',
        };
    }
}