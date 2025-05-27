<?php

namespace App\Environment\Enums;

enum EnvironmentType: string
{
    case PRODUCTION = 'production';
    case STAGING = 'staging';
    case DEVELOPMENT = 'development';
    case TESTING = 'testing';
    case LOCAL = 'local';
    case QA = 'qa';
    case UAT = 'uat';
    case INTEGRATION = 'integration';
    case PREVIEW = 'preview';
    case SANDBOX = 'sandbox';
    case OTHER = 'other';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::PRODUCTION   => 'Production',
            self::STAGING   => 'Staging',
            self::DEVELOPMENT => 'Development',
            self::TESTING => 'Testing',
            self::LOCAL => 'Local',
            self::QA => 'QA',
            self::UAT => 'UAT',
            self::INTEGRATION => 'Integration',
            self::PREVIEW => 'Preview',
            self::SANDBOX => 'Sandbox',
            self::OTHER => 'Other'
        };
    }
}
