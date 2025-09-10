<?php

use App\Environment\Actions\RenderEnvFile;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Livewire\EnvironmentDownloader;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('downloads environment file with selected format without changing environment', function () {
    $user = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $organization = $this->createOrganization(name: 'Ghostbusters', owner: $user);
    $project = $this->createProject(name: 'Website', organization: $organization);
    $env = $this->createEnvironment(name: 'Development', type: EnvironmentType::DEVELOPMENT, project: $project);

    // Set environment default format to grouped
    $env->file_format = EnvFileFormat::GROUPED;
    $env->save();

    // Create known variables
    $creator = app(CreateVariable::class);
    $creator->handle(new CreateVariableData(
        environment: $env,
        key: 'APP_NAME',
        value: 'Ghostable',
        createdBy: $user,
    ));
    $creator->handle(new CreateVariableData(
        environment: $env,
        key: 'DB_HOST',
        value: 'localhost',
        createdBy: $user,
    ));

    $this->actingAs($user);

    $expected = RenderEnvFile::handle(env: $env, format: EnvFileFormat::ALPHABETICAL);

    Livewire::test(EnvironmentDownloader::class, ['environment' => $env->id])
        ->set('fileFormat', EnvFileFormat::ALPHABETICAL)
        ->call('download')
        ->assertFileDownloaded('environment-development.env', $expected);

    expect($env->fresh()->file_format)->toBe(EnvFileFormat::GROUPED);
    expect(Activity::query()->where('event', 'downloaded')->where('subject_id', $env->id)->exists())->toBeTrue();
});
