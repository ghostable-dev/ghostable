<?php

declare(strict_types=1);

use App\Environment\Models\EnvironmentSecret;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Hash;

test('app setup seeds realistic ghostable environment data with version history', function (): void {
    $this->artisan('app:setup', ['--force' => true])->assertSuccessful();

    $organization = Organization::query()
        ->with(['users', 'projects.environments'])
        ->where('slug', 'ghostable')
        ->sole();

    expect($organization->users)->toHaveCount(2);
    expect($organization->users->pluck('email')->sort()->values()->all())
        ->toBe(['nick@gmail.com', 'rucci.joe@gmail.com']);

    $joe = $organization->users->firstWhere('email', 'rucci.joe@gmail.com');

    expect($joe)->not->toBeNull();
    expect(Hash::check('password', $joe->password))->toBeTrue();

    $project = $organization->projects->firstWhere('name', 'Marketing Site');

    expect($project)->not->toBeNull();
    expect($project->environments->pluck('name')->sort()->values()->all())
        ->toBe(['local', 'production', 'staging']);

    $productionEnvironment = $project->environments->firstWhere('name', 'production');
    $stagingEnvironment = $project->environments->firstWhere('name', 'staging');
    $localEnvironment = $project->environments->firstWhere('name', 'local');

    expect($productionEnvironment)->not->toBeNull();
    expect($stagingEnvironment)->not->toBeNull();
    expect($localEnvironment)->not->toBeNull();

    $productionSecrets = EnvironmentSecret::query()
        ->with(['note', 'comments', 'versions.changeNote', 'latestVersion.changeNote'])
        ->where('environment_id', $productionEnvironment->id)
        ->get()
        ->keyBy('name');

    expect($productionSecrets->keys()->all())->toContain(
        'APP_NAME',
        'APP_ENV',
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'QUEUE_CONNECTION',
        'AWS_SECRET_ACCESS_KEY',
        'SENTRY_LARAVEL_DSN',
    );
    expect($productionSecrets->has('SECRET_KEY'))->toBeFalse();
    expect($productionSecrets->has('LOCAL_ACCESS_SECRET'))->toBeFalse();

    $appKey = $productionSecrets->get('APP_KEY');
    $databasePassword = $productionSecrets->get('DB_PASSWORD');
    $awsSecret = $productionSecrets->get('AWS_SECRET_ACCESS_KEY');

    expect($appKey)->not->toBeNull();
    expect($appKey->version)->toBe(2);
    expect($appKey->versions)->toHaveCount(2);
    expect($appKey->note)->not->toBeNull();
    expect($appKey->comments)->toHaveCount(3);
    expect($appKey->latestVersion->changeNote)->not->toBeNull();

    expect($databasePassword)->not->toBeNull();
    expect($databasePassword->version)->toBe(3);
    expect($databasePassword->versions)->toHaveCount(3);
    expect($databasePassword->note)->not->toBeNull();
    expect($databasePassword->comments)->toHaveCount(3);
    expect($databasePassword->versions->filter(fn ($version) => $version->changeNote !== null))
        ->toHaveCount(2);

    expect($awsSecret)->not->toBeNull();
    expect($awsSecret->version)->toBe(2);
    expect($awsSecret->versions)->toHaveCount(2);
    expect($awsSecret->note)->not->toBeNull();
    expect($awsSecret->comments)->toHaveCount(2);
    expect($awsSecret->versions->filter(fn ($version) => $version->changeNote !== null))
        ->toHaveCount(1);

    $stagingSecretNames = EnvironmentSecret::query()
        ->where('environment_id', $stagingEnvironment->id)
        ->pluck('name')
        ->sort()
        ->values()
        ->all();

    expect($stagingSecretNames)->toContain(
        'APP_KEY',
        'APP_URL',
        'DB_PASSWORD',
        'QUEUE_CONNECTION',
        'AWS_SECRET_ACCESS_KEY',
    );
    expect($stagingSecretNames)->not->toContain(
        'FEATURE_CLI_DEVICE_REPAIR',
        'GHOSTABLE_API_BASE',
        'LOCAL_ACCESS_SECRET',
    );

    $localQueueSecret = EnvironmentSecret::query()
        ->with(['versions.changeNote'])
        ->where('environment_id', $localEnvironment->id)
        ->where('name', 'QUEUE_CONNECTION')
        ->sole();

    $seededSecrets = EnvironmentSecret::query()
        ->with(['environment', 'note', 'comments', 'versions.changeNote'])
        ->whereIn('environment_id', [
            $productionEnvironment->id,
            $stagingEnvironment->id,
            $localEnvironment->id,
        ])
        ->get();

    expect($localQueueSecret->version)->toBe(2);
    expect($localQueueSecret->versions)->toHaveCount(2);
    expect($localQueueSecret->versions->filter(fn ($version) => $version->changeNote !== null))
        ->toHaveCount(1);

    foreach ($seededSecrets as $secret) {
        expect($secret->alg)->toBe('xchacha20-poly1305');
        expect(base64_decode($secret->client_sig, true))->not->toBeFalse();
        expect($secret->aad)->toMatchArray([
            'org' => 'ghostable',
            'project' => 'marketing-site',
            'env' => $secret->environment->name,
            'name' => $secret->name,
        ]);

        if ($secret->note !== null) {
            expect($secret->note->alg)->toBe('xchacha20-poly1305');
            expect(base64_decode($secret->note->client_sig, true))->not->toBeFalse();
        }

        foreach ($secret->comments as $comment) {
            expect($comment->alg)->toBe('xchacha20-poly1305');
            expect(base64_decode($comment->client_sig, true))->not->toBeFalse();
        }

        foreach ($secret->versions as $version) {
            if ($version->changeNote === null) {
                continue;
            }

            expect($version->changeNote->alg)->toBe('xchacha20-poly1305');
            expect(base64_decode($version->changeNote->client_sig, true))->not->toBeFalse();
        }
    }
});
