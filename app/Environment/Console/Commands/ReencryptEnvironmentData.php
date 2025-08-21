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
        $this->info('Re-encrypting environment variables...');
        EnvironmentVariable::withTrashed()
            ->with('environment')
            ->chunk(100, function ($variables) {
                foreach ($variables as $variable) {
                    $value = $variable->value;

                    if ($value === null) {
                        continue;
                    }

                    $variable->forceFill(['value' => $value])->saveQuietly();
                }
            });

        $this->info('Re-encrypting environment variable versions...');
        EnvironmentVariableVersion::with('variable.environment')
            ->chunk(100, function ($versions) {
                foreach ($versions as $version) {
                    $value = $version->value;

                    if ($value === null) {
                        continue;
                    }

                    $version->forceFill(['value' => $value])->saveQuietly();
                }
            });

        $this->info('Re-encrypting secrets...');
        Secret::withTrashed()
            ->with('environment')
            ->chunk(100, function ($secrets) {
                foreach ($secrets as $secret) {
                    $value = $secret->value;

                    if ($value === null) {
                        continue;
                    }

                    $secret->forceFill(['value' => $value])->saveQuietly();
                }
            });

        $this->info('Re-encrypting secret versions...');
        SecretVersion::with('secret.environment')
            ->chunk(100, function ($versions) {
                foreach ($versions as $version) {
                    $value = $version->value;

                    if ($value === null) {
                        continue;
                    }

                    $version->forceFill(['value' => $value])->saveQuietly();
                }
            });

        $this->info('Re-encryption complete.');

        return self::SUCCESS;
    }
}
