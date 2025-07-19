<?php

namespace App\Secret\Enums;

use App\Core\Concerns\HasLabel;

enum SecretType: string
{
    use HasLabel;

    case GENERIC = 'generic';
    case CERTIFICATE = 'certificate';
    case SSH_KEY = 'ssh_key';
    case TOKEN = 'token';
    case JSON_BLOB = 'json_blob';

    public function label(): string
    {
        return match ($this) {
            self::GENERIC => 'Generic',
            self::CERTIFICATE => 'Certificate',
            self::SSH_KEY => 'SSH Key',
            self::TOKEN => 'Token',
            self::JSON_BLOB => 'JSON Blob',
        };
    }
}
