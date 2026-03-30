<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Concerns;

use App\Crypto\Enums\DeviceClientType;
use Illuminate\Http\Request;

trait ResolvesApiActivitySource
{
    private function resolveApiActivitySource(Request $request, ?string $fallback = null): string
    {
        return $this->normalizeApiActivitySource($request->header('X-Ghostable-Client-Type'))
            ?? $this->normalizeApiActivitySource($fallback)
            ?? $this->normalizeApiActivitySource($request->input('client_type'))
            ?? DeviceClientType::Cli->value;
    }

    private function normalizeApiActivitySource(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return DeviceClientType::tryFrom(strtolower(trim($value)))?->value;
    }
}
