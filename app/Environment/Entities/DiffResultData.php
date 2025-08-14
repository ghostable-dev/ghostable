<?php

namespace App\Environment\Entities;

use Spatie\LaravelData\Data;

class DiffResultData extends Data
{
    /**
     * @param  array<string, array{value: ?string, commented: bool}>  $added
     * @param  array<string, array{current: array{value: ?string, commented: bool}, incoming: array{value: ?string, commented: bool}}>  $updated
     * @param  array<string, array{value: ?string, commented: bool}>  $removed
     */
    public function __construct(
        public array $added = [],
        public array $updated = [],
        public array $removed = [],
    ) {}
}
