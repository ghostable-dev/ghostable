<?php

use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\GetSuggestedRuleKeys;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;
use App\Environment\Variable\Registry\VariableRegistry;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('suggests registry and project keys not used', function () {
    $project = Project::factory()->create();
    $target = Environment::factory()->forProject($project)->create();
    $other = Environment::factory()->forProject($project)->create();

    $registry = new VariableRegistry;
    $registry->register(new class extends VariableDefinition
    {
        public function key(): string
        {
            return 'APP_FOO';
        }

        public function group(): VariableGroup
        {
            return VariableGroup::App;
        }
    });
    $registry->register(new class extends VariableDefinition
    {
        public function key(): string
        {
            return 'APP_BAR';
        }

        public function group(): VariableGroup
        {
            return VariableGroup::App;
        }
    });
    app()->instance(VariableRegistry::class, $registry);

    $target->rules()->create([
        'key' => 'APP_FOO',
        'is_required' => true,
        'type' => \App\Environment\Validation\Enums\EnvironmentVariableRuleType::STRING,
    ]);

    DB::table('environment_variables')->insert([
        'id' => (string) Str::uuid(),
        'environment_id' => $other->id,
        'key' => 'OTHER_KEY',
        'value' => 'baz',
        'is_commented' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $suggested = app(GetSuggestedRuleKeys::class)->handle($target);

    expect($suggested)->toHaveKey('App')
        ->and($suggested['App'])->toBe(['APP_BAR'])
        ->and($suggested['Other Project Keys'])->toBe(['OTHER_KEY']);
});
