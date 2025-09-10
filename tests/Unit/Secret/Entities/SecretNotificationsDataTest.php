<?php

use App\Secret\Entities\SecretNotificationsData;

it('defaults secret_updated to true', function () {
    $data = new SecretNotificationsData;
    expect($data->secret_updated)->toBeTrue();

    $data = new SecretNotificationsData(secret_updated: false);
    expect($data->secret_updated)->toBeFalse();
});
