<?php

namespace App\Environment\Variable\Enums;

enum DeliveryMode: string
{
    case STANDARD = 'standard';
    case SECRET = 'secret';
    case ENCRYPTED = 'encrypted';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::SECRET => 'Secret',
            self::ENCRYPTED => 'Encrypted',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::STANDARD => 'Stored as a plain Vapor environment variable (~2 KB total limit).',
            self::SECRET => 'Stored as a Vapor Secret (AWS Parameter Store, no size cap).',
            self::ENCRYPTED => 'Shipped in an encrypted .env file with the deploy artifact (no size cap).',
        };
    }
}
