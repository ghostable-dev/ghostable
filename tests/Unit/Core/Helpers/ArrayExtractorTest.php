<?php

use App\Core\Helpers\ArrayExtractor;
use Tests\TestCase;

uses(TestCase::class);

it('extracts values from nested arrays', function () {
    $source = [
        'user' => [
            'name' => [
                'first' => 'John',
                'last' => 'Doe',
            ],
        ],
    ];

    $fields = [
        'first' => 'user.name.first',
        'last' => ['user', 'name', 'last'],
        'missing' => 'user.address',
    ];

    $result = ArrayExtractor::extract($source, $fields);

    expect($result)->toBe([
        'first' => 'John',
        'last' => 'Doe',
        'missing' => null,
    ]);
});

it('returns null when base array is null', function () {
    $method = new ReflectionMethod(ArrayExtractor::class, 'getValue');
    $method->setAccessible(true);

    expect($method->invoke(null, null, 'foo'))->toBeNull();
});
