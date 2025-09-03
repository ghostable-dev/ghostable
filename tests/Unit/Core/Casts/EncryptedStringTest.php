<?php

use App\Account\Models\User;
use App\Core\Casts\EncryptedString;
use Tests\TestCase;

uses(TestCase::class);

it('encrypts and decrypts values', function () {
    $cast = new EncryptedString;
    $model = new User;

    $encrypted = $cast->set($model, 'secret', 'plain', []);
    expect($encrypted)->not->toBe('plain');

    $decrypted = $cast->get($model, 'secret', $encrypted, []);
    expect($decrypted)->toBe('plain');
});

it('returns null for non scalar values', function () {
    $cast = new EncryptedString;
    $model = new User;

    $result = $cast->set($model, 'secret', ['array'], []);
    expect($result)->toBeNull();
});

it('returns null on decryption failure', function () {
    $cast = new EncryptedString;
    $model = new User;

    $result = $cast->get($model, 'secret', 'not-encrypted', []);
    expect($result)->toBeNull();
});
