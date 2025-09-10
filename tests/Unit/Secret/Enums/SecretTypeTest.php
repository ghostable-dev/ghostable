<?php

use App\Secret\Enums\SecretType;

it('provides labels for secret types', function () {
    expect(SecretType::GENERIC->label())->toBe('Generic')
        ->and(SecretType::CERTIFICATE->label())->toBe('Certificate')
        ->and(SecretType::SSH_KEY->label())->toBe('SSH Key')
        ->and(SecretType::TOKEN->label())->toBe('Token')
        ->and(SecretType::JSON_BLOB->label())->toBe('JSON Blob');
});
