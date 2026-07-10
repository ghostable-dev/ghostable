<?php

use App\Licensing\Actions\CanonicalJson;
use App\Licensing\Actions\SignLicenseEntitlement;
use App\Licensing\Actions\VerifyLicenseEntitlement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('canonical license entitlement json is stable for reordered keys', function () {
    $canonicalJson = app(CanonicalJson::class);

    $first = [
        'license' => [
            'plan' => 'personal',
            'status' => 'active',
        ],
        'schema_version' => 1,
    ];

    $second = [
        'schema_version' => 1,
        'license' => [
            'status' => 'active',
            'plan' => 'personal',
        ],
    ];

    expect($canonicalJson->encode($first))->toBe($canonicalJson->encode($second));
});

it('signed license entitlements verify and tampered payloads fail', function () {
    configureLicenseSigningKeys();

    $payload = licenseSigningPayload();
    $signed = app(SignLicenseEntitlement::class)->execute($payload);

    expect(app(VerifyLicenseEntitlement::class)->execute($signed))->toBeTrue();

    $signed['payload']['license']['plan'] = 'business';

    expect(app(VerifyLicenseEntitlement::class)->execute($signed))->toBeFalse();
});

it('old license signing key ids can verify with public-only config', function () {
    $keys = configureLicenseSigningKeys();
    $payload = licenseSigningPayload();
    $signature = sodium_crypto_sign_detached(app(CanonicalJson::class)->encode($payload), $keys['old']['private_key_raw']);

    $signed = [
        'payload' => $payload,
        'signature' => base64_encode($signature),
        'key_id' => 'old-key',
        'algorithm' => 'Ed25519',
    ];

    expect(app(VerifyLicenseEntitlement::class)->execute($signed))->toBeTrue();
});

/**
 * @return array<string, array<string, string>>
 */
function configureLicenseSigningKeys(): array
{
    $currentKeypair = sodium_crypto_sign_keypair();
    $oldKeypair = sodium_crypto_sign_keypair();

    $keys = [
        'current' => [
            'public_key' => base64_encode(sodium_crypto_sign_publickey($currentKeypair)),
            'private_key' => base64_encode(sodium_crypto_sign_secretkey($currentKeypair)),
            'private_key_raw' => sodium_crypto_sign_secretkey($currentKeypair),
        ],
        'old' => [
            'public_key' => base64_encode(sodium_crypto_sign_publickey($oldKeypair)),
            'private_key_raw' => sodium_crypto_sign_secretkey($oldKeypair),
        ],
    ];

    config()->set('license.signing.active_key_id', 'current-key');
    config()->set('license.signing.keys', [
        'current-key' => [
            'public_key' => $keys['current']['public_key'],
            'private_key' => $keys['current']['private_key'],
        ],
        'old-key' => [
            'public_key' => $keys['old']['public_key'],
        ],
    ]);

    return $keys;
}

/**
 * @return array<string, mixed>
 */
function licenseSigningPayload(): array
{
    return [
        'schema_version' => 1,
        'license' => [
            'id' => '00000000-0000-4000-8000-000000000001',
            'status' => 'active',
            'plan' => 'personal',
            'features' => ['desktop'],
            'seat_count' => 1,
            'activation_limit' => 2,
            'updates_until' => '2027-06-18T00:00:00+00:00',
            'expires_at' => null,
        ],
        'activation' => [
            'id' => '00000000-0000-4000-8000-000000000002',
            'status' => 'active',
            'machine_fingerprint_hash' => hash('sha256', 'machine-alpha'),
            'machine_name' => 'Joe MacBook',
            'platform' => 'macos',
            'app_version' => '0.1.0',
            'last_validated_at' => null,
            'deactivated_at' => null,
        ],
        'issued_at' => '2026-06-18T00:00:00+00:00',
        'valid_until' => '2026-06-19T00:00:00+00:00',
    ];
}
