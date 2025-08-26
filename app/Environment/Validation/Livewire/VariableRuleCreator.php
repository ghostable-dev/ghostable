<?php

namespace App\Environment\Validation\Livewire;

use App\Environment\Livewire\EnvironmentActivity;
use App\Environment\Livewire\EnvironmentComponent;
use App\Environment\Validation\Actions\CreateVariableRule;
use App\Environment\Validation\Actions\GetSuggestedRuleKeys;
use App\Environment\Validation\Entities\CreateVariableRuleData;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Rules\VariableRuleFormRules;
use App\Environment\Variable\Actions\NormalizeVariableKey;
use App\Organization\Enums\OrganizationPermission;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class VariableRuleCreator extends EnvironmentComponent
{
    /**
     * Indicates whether the variable editor modal is currently visible.
     */
    public bool $showing = false;

    /**
     * Event name used to trigger the variable
     * rule creator modal.
     */
    public const LAUNCH = 'variable-rule-creator:launch';

    /**
     * Event name used to trigger the variable rule
     * creator modal added a variable.
     */
    public const ADDED = 'variable-rule-creator:added';

    /**
     * The unique identifier for the environment variable.
     */
    public string $key = '';

    /**
     * Whether this environment variable is required.
     */
    public bool $is_required = true;

    /**
     * The type of the environment variable, represented as an enum.
     */
    public EnvironmentVariableRuleType $type = EnvironmentVariableRuleType::STRING;

    /**
     * The minimum allowed.
     */
    public ?int $min = null;

    /**
     * The maximum allowed.
     */
    public ?int $max = null;

    /**
     * A list of explicitly permitted values (strings).
     *
     * @var string[]
     */
    public array $allowed_values = [];

    /**
     * An optional human-readable description of the rule.
     */
    public ?string $description = null;

    /**
     * Livewire event listener to launch the environment rule creator modal.
     */
    #[On(self::LAUNCH)]
    public function launch(): void
    {
        $this->showing = true;
    }

    /**
     * Get a list of suggested environment variable
     * validation keys for the current environment.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function keySuggestions(): array
    {
        return app(GetSuggestedRuleKeys::class)->handle($this->environment);
    }

    /**
     * Get the available rule types for environment variable validation.
     *
     * @return EnvironmentVariableRuleType[] Array of all defined rule types.
     */
    #[Computed]
    public function ruleTypeOptions(): array
    {
        return EnvironmentVariableRuleType::cases();
    }

    /**
     * Livewire lifecycle hook: triggered when the `key` property is updated.
     *
     * Normalizes the environment variable key by converting it to an uppercase
     * slug-style string using underscores (e.g., "app url" becomes "APP_URL").
     */
    public function updatedKey($value)
    {
        $this->key = app(NormalizeVariableKey::class)->handle($value);
    }

    /**
     * Validate input and create a new environment variable rule.
     *
     * Performs the following steps:
     * 1. Validates the component’s properties against the defined rules.
     * 2. Invokes the CreateRule action to persist the new rule.
     * 3. Resets the Livewire properties to their defaults.
     * 4. Displays a success toast message.
     * 5. Dispatches the `ADDED` event with the new rule’s ID.
     * 6. Closes the “add rule” form/modal.
     */
    public function add(): void
    {
        $this->authorize('perform', [$this->environment, OrganizationPermission::ManageValidationRules]);

        $rules = VariableRuleFormRules::createRules($this->environment);

        $validated = $this->validate($rules);

        $rule = app(CreateVariableRule::class)->handle($this->toCreateRuleData($validated));

        Flux::toast(
            variant: 'success',
            heading: 'Rule Created',
            text: "New rule for key \"{$this->key}\" added."
        );

        $this->dispatch(self::ADDED, $rule->id);
        $this->dispatch(EnvironmentActivity::ACTIVITY_UPDATED);

        $this->showing = false;
        $this->resetForm();
    }

    private function toCreateRuleData(array $input): CreateVariableRuleData
    {
        return new CreateVariableRuleData(
            environment: $this->environment,
            key: $input['key'],
            isRequired: $input['is_required'],
            type: $input['type'],
            min: $input['min'] ?? null,
            max: $input['max'] ?? null,
            allowedValues: $input['allowed_values'] ?? [],
            description: $input['description'] ?? null,
            isOverride: false,
            isDeleted: false,
            createdBy: Auth::user(),
        );
    }

    /**
     * Reset the form properties to their default state.
     *
     * This will clear out all the input fields used when creating
     * or editing an environment variable rule.
     */
    protected function resetForm(): void
    {
        $this->reset([
            'key',
            'is_required',
            'type',
            'min',
            'max',
            'allowed_values',
            'description',
        ]);
    }

    public function render()
    {
        return view('environment.validation.variable-rule-creator');
    }
}
