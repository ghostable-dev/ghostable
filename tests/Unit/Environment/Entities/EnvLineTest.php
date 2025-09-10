<?php

use App\Environment\Enums\EnvLineType;
use App\Environment\Services\EnvParser;
use Tests\TestCase;

uses(TestCase::class);

it('handles parsed env lines', function () {
    $parser = new EnvParser;
    $lines = collect($parser->parse([
        'FOO=bar',
        '#BAR=baz',
        'BAD LINE',
        'QUO="a \"b\""',
    ]));

    $foo = $lines->firstWhere('key', 'FOO');
    $bar = $lines->firstWhere('key', 'BAR');
    $bad = $lines->firstWhere('type', EnvLineType::INVALID);
    $quo = $lines->firstWhere('key', 'QUO');

    expect($foo->isValid())->toBeTrue()
        ->and($foo->toArray())->toBe([
            'type' => 'env',
            'key' => 'FOO',
            'value' => 'bar',
            'commented' => false,
            'raw' => 'FOO=bar',
            'error' => null,
        ])
        ->and($bar->isValid())->toBeTrue()
        ->and($bar->commented)->toBeTrue()
        ->and($bad->isValid())->toBeFalse()
        ->and($quo->value)->toBe('a "b"');
});
