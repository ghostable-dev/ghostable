<?php

use Illuminate\Testing\TestResponse;

it('returns 410 for any v1 endpoint', function () {
    /** @var TestResponse $response */
    $response = $this->getJson('/api/v1/anything-here');

    $response->assertStatus(410)
        ->assertJson([
            'message' => 'API v1 has been retired. Please upgrade to API v2.',
        ]);
});
