<?php

namespace App\Secret\Console\Commands;

use App\Environment\Models\Environment;
use App\Secret\Models\Secret;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MigrateProjectSecrets extends Command
{
    protected $signature = 'secrets:migrate-project-secrets';

    protected $description = 'Copy project-level secrets to each environment and remove the original records';

    public function handle(): int
    {
        Secret::whereNull('environment_id')
            ->whereNotNull('metadata->project_id')
            ->chunk(100, function ($secrets) {
                foreach ($secrets as $secret) {
                    $projectId = data_get($secret->metadata, 'project_id');

                    $environments = Environment::where('project_id', $projectId)->get();

                    foreach ($environments as $environment) {
                        $newSecret = $secret->replicate();
                        $newSecret->id = (string) Str::uuid();
                        $newSecret->environment_id = $environment->id;
                        $newSecret->save();

                        foreach ($secret->versions as $version) {
                            $newVersion = $version->replicate();
                            $newVersion->id = (string) Str::uuid();
                            $newVersion->secret_id = $newSecret->id;
                            $newVersion->save();
                        }
                    }

                    $secret->versions()->delete();
                    $secret->delete();
                }
            });

        $this->info('Project secrets migrated to environments.');

        return self::SUCCESS;
    }
}
