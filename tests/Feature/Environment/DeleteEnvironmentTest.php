<?php

use App\Environment\Enums\EnvironmentType;
use App\Environment\Livewire\EnvironmentGeneralSettings;
use App\Environment\Variable\Actions\CreateVariable;
use App\Environment\Variable\Entities\CreateVariableData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('deleting base environment detaches derived environments', function () {
    $user = $this->createUser('User', 'user@example.com');
    $organization = $this->createOrganization('Org', $user);
    $project = $this->createProject('Proj', $organization);

    $base = $this->createEnvironment('Base', EnvironmentType::DEVELOPMENT, $project);
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $base,
        key: 'FOO',
        value: 'base',
    ));

    $child = $this->createEnvironment('Child', EnvironmentType::DEVELOPMENT, $project, base: $base);
    app(CreateVariable::class)->handle(new CreateVariableData(
        environment: $child,
        key: 'FOO',
        value: 'child',
        is_override: true,
    ));

    $this->actingAs($user);

    Livewire::test(EnvironmentGeneralSettings::class, ['environment' => $base])
        ->call('deleteEnvironment');

    $child = $child->fresh();

    expect($child->base_id)->toBeNull();
    $var = $child->variables()->where('key', 'FOO')->first();
    expect((bool) $var->is_override)->toBeFalse();
});

