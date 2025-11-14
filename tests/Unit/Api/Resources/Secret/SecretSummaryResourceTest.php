<?php

use App\Api\Core\Resources\Secret\SecretSummaryResource;
use App\Secret\Enums\SecretType;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('transforms secret summary', function () {
    $model = (object) [
        'id' => '1',
        'name' => 'Test',
        'type' => SecretType::GENERIC,
        'metadata' => ['a' => 'b'],
        'created_at' => Carbon::parse('2024-01-01', 'UTC'),
        'updated_at' => Carbon::parse('2024-01-02', 'UTC'),
    ];

    $data = (new SecretSummaryResource($model))->toArray(request());

    expect($data)->toMatchArray([
        'id' => '1',
        'name' => 'Test',
        'type' => 'generic',
        'metadata' => ['a' => 'b'],
        'created_at' => '2024-01-01T00:00:00+00:00',
        'updated_at' => '2024-01-02T00:00:00+00:00',
    ]);
});
