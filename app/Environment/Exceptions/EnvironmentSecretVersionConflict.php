<?php

declare(strict_types=1);

namespace App\Environment\Exceptions;

use RuntimeException;

final class EnvironmentSecretVersionConflict extends RuntimeException
{
    public function __construct(
        public readonly string $key,
        public readonly int $serverVersion,
        public readonly int $clientIfVersion,
        string $message = 'Environment secret version conflict detected.'
    ) {
        parent::__construct($message);
    }

    /**
     * @return array{key:string, server_version:int, client_if_version:int}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'server_version' => $this->serverVersion,
            'client_if_version' => $this->clientIfVersion,
        ];
    }
}
