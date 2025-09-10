<?php

use App\Api\Resources\Secret\SecretTypeResource;
use App\Secret\Enums\SecretType;
use Tests\TestCase;

uses(TestCase::class);

it('transforms secret type', function () {
    $resource = new SecretTypeResource(SecretType::TOKEN);

    expect($resource->toArray(request()))->toBe([
        'value' => 'token',
        'label' => 'Token',
    ]);
});
