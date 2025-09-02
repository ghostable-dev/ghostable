<?php

use App\Core\Casts\EncryptedString;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

uses(TestCase::class);

class DummyModel extends Model
{
    protected $table = 'dummy';
}

it('encrypts and decrypts values', function () {
    $cast = new EncryptedString;
    $model = new DummyModel;

    $encrypted = $cast->set($model, 'secret', 'plain', []);
    expect($encrypted)->not->toBe('plain');

    $decrypted = $cast->get($model, 'secret', $encrypted, []);
    expect($decrypted)->toBe('plain');
});

it('returns null for non scalar values', function () {
    $cast = new EncryptedString;
    $model = new DummyModel;

    $result = $cast->set($model, 'secret', ['array'], []);
    expect($result)->toBeNull();
});

it('returns null on decryption failure', function () {
    $cast = new EncryptedString;
    $model = new DummyModel;

    $result = $cast->get($model, 'secret', 'not-encrypted', []);
    expect($result)->toBeNull();
});
