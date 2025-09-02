<?php

use App\Secret\Enums\SecretNotification;

it('provides labels and descriptions', function () {
    expect(SecretNotification::SECRET_UPDATED->label())->toBe('Secret Updated')
        ->and(SecretNotification::SECRET_UPDATED->description())->toBe('A secret was updated.');
});
