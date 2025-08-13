<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Variable\Livewire\VariableImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('imports environment file and logs activity', function () {
    $user = $this->createUser(name: 'Egon', email: 'egon@ghostbusters.com');
    $team = $this->createTeam(name: 'Ghostbusters', owner: $user);
    $project = $this->createProject(name: 'Website', team: $team);
    $env = $this->createEnvironment(name: 'Development', type: EnvironmentType::DEVELOPMENT, project: $project);

    $this->actingAs($user);

    Livewire::test(VariableImporter::class, ['environment' => $env->id])
        ->set('input', "FOO=BAR\n")
        ->call('import');

    expect($env->fresh()->variables()->where('key', 'FOO')->exists())->toBeTrue();
    expect(Activity::query()->where('event', 'imported')->where('subject_id', $env->id)->exists())->toBeTrue();
});
