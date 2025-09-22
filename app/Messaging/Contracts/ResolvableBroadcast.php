<?php

namespace App\Messaging\Contracts;

interface ResolvableBroadcast
{
    /** True if this class knows how to parse/own the given key */
    public static function supportsKey(string $key): bool;

    /** Build a campaign instance from the given key */
    public static function fromKey(string $key): Campaign;

    /** Convenience: build a key for a given identifier (e.g., post id) */
    public static function makeKey(string $id): string;
}
