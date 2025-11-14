<?php

use App\Api\V1\Http\Controllers\Secret\GetSecretTypes;
use App\Secret\Enums\SecretType;
use Tests\TestCase;

uses(TestCase::class);

it('returns all secret types', function () {
    $controller = new GetSecretTypes;

    $resource = $controller();
    $data = $resource->response()->getData(true);

    expect($data['data'])->toHaveCount(count(SecretType::cases()))
        ->and($data['data'][0])->toMatchArray([
            'value' => SecretType::cases()[0]->value,
            'label' => SecretType::cases()[0]->label(),
        ]);
});
