<?php

namespace App\Environment\Validation\Entities;

use App\Environment\Validation\Contracts\KeyRuleProvider;
use Spatie\LaravelData\Data;

/**
 * Represents all validation rules associated with a single environment key.
 */
final class FieldRules extends Data
{
    /**
     * @param  KeyRuleProvider[]  $providers
     */
    public function __construct(
        public string $key,

        // #[DataCollectionOf(KeyRuleProvider::class)]
        public array $providers,
    ) {}
}
