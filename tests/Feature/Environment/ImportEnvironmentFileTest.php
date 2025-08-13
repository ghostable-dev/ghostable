<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('imports environment file and logs activity', function () {
    $user = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $team = $this->createTeam(name: 'Ghostbusters', owner: $user);
    $project = $this->createProject(name: 'Website', team: $team);
    $env = $this->createEnvironment(name: 'Development', type: EnvironmentType::DEVELOPMENT, project: $project);

    $this->actingAs($user);

    $file = UploadedFile::fake()->createWithContent('env.env', "FOO=BAR\n");

    Livewire::test(VariableManager::class, ['environment' => $env])
        ->set('envUpload', $file)
        ->call('importEnvFile');

    expect($env->fresh()->variables()->where('key', 'FOO')->exists())->toBeTrue();
    expect(Activity::query()->where('event', 'imported')->where('subject_id', $env->id)->exists())->toBeTrue();
    expect(Activity::query()->where('event', 'push')->where('subject_id', $env->id)->exists())->toBeTrue();
});
