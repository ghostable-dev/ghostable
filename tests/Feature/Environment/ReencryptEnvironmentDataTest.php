<?php

use App\Account\Models\User;
use App\Environment\Console\Commands\ReencryptEnvironmentData;
use App\Environment\Models\Environment;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;
use App\Secret\Enums\SecretType;
use App\Secret\Models\Secret;
use App\Secret\Versioning\Models\SecretVersion;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('re-encrypts legacy secret and variable data with environment keys', function () {
    $user = User::factory()->create();
    $team = $this->createTeam('team', $user);
    $project = $this->createProject('proj', $team);
    $environment = Environment::factory()->forProject($project)->create();

    $appEncrypter = app('encrypter');
    $envEncrypter = $environment->encrypter();

    // Environment variable with legacy encryption
    $variable = EnvironmentVariable::factory()->forEnvironment($environment)->create([
        'key' => 'FOO',
        'value' => 'bar',
    ]);

    DB::table('environment_variables')
        ->where('id', $variable->id)
        ->update(['value' => $appEncrypter->encryptString('legacy-var')]);
    $variable->refresh();

    // Environment variable version with legacy encryption
    $version = EnvironmentVariableVersion::forceCreate([
        'id' => (string) Str::uuid(),
        'environment_variable_id' => $variable->id,
        'key' => 'FOO',
        'value' => '',
        'is_commented' => 0,
        'version' => 1,
    ]);
    DB::table('environment_variable_versions')
        ->where('id', $version->id)
        ->update(['value' => $appEncrypter->encryptString('legacy-var-ver')]);
    $version->refresh();

    // Secret with legacy encryption
    $secret = Secret::forceCreate([
        'id' => (string) Str::uuid(),
        'environment_id' => $environment->id,
        'name' => 'secret',
        'type' => SecretType::GENERIC,
        'value_encrypted' => '',
        'created_by_id' => $user->id,
    ]);
    DB::table('secrets')
        ->where('id', $secret->id)
        ->update(['value_encrypted' => $appEncrypter->encryptString('legacy-secret')]);
    $secret->refresh();

    // Secret version with legacy encryption
    $secretVersion = SecretVersion::forceCreate([
        'id' => (string) Str::uuid(),
        'secret_id' => $secret->id,
        'name' => 'secret',
        'type' => SecretType::GENERIC,
        'value_encrypted' => '',
        'version' => 1,
        'changed_by' => $user->id,
    ]);
    DB::table('secret_versions')
        ->where('id', $secretVersion->id)
        ->update(['value_encrypted' => $appEncrypter->encryptString('legacy-secret-ver')]);
    $secretVersion->refresh();

    // Ensure legacy values decrypt with app key before command
    expect($appEncrypter->decryptString($variable->getRawOriginal('value')))->toBe('legacy-var');
    expect($appEncrypter->decryptString($version->getRawOriginal('value')))->toBe('legacy-var-ver');
    expect($appEncrypter->decryptString($secret->getRawOriginal('value_encrypted')))->toBe('legacy-secret');
    expect($appEncrypter->decryptString($secretVersion->getRawOriginal('value_encrypted')))->toBe('legacy-secret-ver');

    // Run command
    Artisan::call(ReencryptEnvironmentData::class);

    // Refresh models
    $variable->refresh();
    $version->refresh();
    $secret->refresh();
    $secretVersion->refresh();

    // Values can now be decrypted with environment key
    expect($envEncrypter->decryptString($variable->getRawOriginal('value')))->toBe('legacy-var');
    expect($envEncrypter->decryptString($version->getRawOriginal('value')))->toBe('legacy-var-ver');
    expect($envEncrypter->decryptString($secret->getRawOriginal('value_encrypted')))->toBe('legacy-secret');
    expect($envEncrypter->decryptString($secretVersion->getRawOriginal('value_encrypted')))->toBe('legacy-secret-ver');

    // App key no longer works
    expect(fn () => $appEncrypter->decryptString($variable->getRawOriginal('value')))->toThrow(DecryptException::class);
    expect(fn () => $appEncrypter->decryptString($version->getRawOriginal('value')))->toThrow(DecryptException::class);
    expect(fn () => $appEncrypter->decryptString($secret->getRawOriginal('value_encrypted')))->toThrow(DecryptException::class);
    expect(fn () => $appEncrypter->decryptString($secretVersion->getRawOriginal('value_encrypted')))->toThrow(DecryptException::class);
});
