<?php

use App\Core\Models\Activity;
use App\Integration\Integrations\Drata\DrataClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not send activity when api key is missing', function () {
    config()->set('drata.api_key', null);
    config()->set('drata.base_url', 'https://drata.test');

    Http::fake();

    $activity = new Activity;
    $activity->event = 'test';

    resolve(DrataClient::class)->sendActivity($activity);

    Http::assertNothingSent();
});
