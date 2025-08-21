<?php

namespace App\Environment\Console\Commands;

use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Versioning\Models\EnvironmentVariableVersion;
use App\Secret\Models\Secret;
use App\Secret\Versioning\Models\SecretVersion;
use Illuminate\Console\Command;

class ReencryptEnvironmentData extends Command
{
    protected $signature = 'environments:re-encrypt';

    protected $description = 'Re-encrypt secrets, variables, and their versions using environment keys';

    public function handle(): int
    {
        $appEncrypter = app('encrypter');

        $this->info('Re-encrypting environment variables...');
        EnvironmentVariable::withTrashed()
            ->with('environment')
            ->chunk(100, function ($variables) use ($appEncrypter) {
                foreach ($variables as $variable) {
                    $raw = $variable->getRawOriginal('value');

                    if ($raw === null) {
                        continue;
                    }

                    try {
                        $decrypted = $appEncrypter->decryptString($raw);
                    } catch (\Throwable $e) {
                        continue;
                    }

                    $variable->forceFill([
                        'value' => $decrypted,
                    ])->saveQuietly();
                }
            });

        $this->info('Re-encrypting environment variable versions...');
        EnvironmentVariableVersion::with('variable.environment')
            ->chunk(100, function ($versions) use ($appEncrypter) {
                foreach ($versions as $version) {
                    $raw = $version->getRawOriginal('value');

                    if ($raw === null) {
                        continue;
                    }

                    try {
                        $decrypted = $appEncrypter->decryptString($raw);
                    } catch (\Throwable $e) {
                        continue;
                    }

                    $version->forceFill([
                        'value' => $decrypted,
                    ])->saveQuietly();
                }
            });

        $this->info('Re-encrypting secrets...');
        Secret::withTrashed()
            ->with('environment')
            ->chunk(100, function ($secrets) use ($appEncrypter) {
                foreach ($secrets as $secret) {
                    $raw = $secret->getRawOriginal('value_encrypted');

                    if ($raw === null) {
                        continue;
                    }

                    try {
                        $decrypted = $appEncrypter->decryptString($raw);
                    } catch (\Throwable $e) {
                        continue;
                    }

                    $secret->forceFill([
                        'value' => $decrypted,
                    ])->saveQuietly();
                }
            });

        $this->info('Re-encrypting secret versions...');
        SecretVersion::with('secret.environment')
            ->chunk(100, function ($versions) use ($appEncrypter) {
                foreach ($versions as $version) {
                    $raw = $version->getRawOriginal('value_encrypted');

                    if ($raw === null) {
                        continue;
                    }

                    try {
                        $decrypted = $appEncrypter->decryptString($raw);
                    } catch (\Throwable $e) {
                        continue;
                    }

                    $version->forceFill([
                        'value' => $decrypted,
                    ])->saveQuietly();
                }
            });

        $this->info('Re-encryption complete.');

        return self::SUCCESS;
    }
}
