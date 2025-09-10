<?php

use App\Secret\Casts\EncryptedSecretValue;
use App\Secret\Models\Secret;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;

it('throws when model is not a Secret', function () {
    $cast = new EncryptedSecretValue;
    $model = new class extends Model {};

    $method = (new ReflectionClass(EncryptedSecretValue::class))->getMethod('encrypterForModel');
    $method->setAccessible(true);

    $method->invoke($cast, $model);
})->throws(InvalidArgumentException::class);

it('returns encrypter for secret model', function () {
    $cast = new EncryptedSecretValue;
    $secret = new class extends Secret
    {
        public function encrypter(): Encrypter
        {
            return new Encrypter(random_bytes(32), 'AES-256-CBC');
        }
    };

    $method = (new ReflectionClass(EncryptedSecretValue::class))->getMethod('encrypterForModel');
    $method->setAccessible(true);
    $encrypter = $method->invoke($cast, $secret);

    expect($encrypter)->toBeInstanceOf(Encrypter::class);
});
