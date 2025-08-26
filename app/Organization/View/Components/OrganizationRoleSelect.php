<?php

namespace App\Organization\View\Components;

use App\Organization\Enums\OrganizationRole;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OrganizationRoleSelect extends Component
{
    public function __construct() {}

    public function roles(): array
    {
        return OrganizationRole::cases();
    }

    public function render(): View|Closure|string
    {
        return <<<'blade'
        <flux:radio.group {{ $attributes }} label="Role">
            @foreach($roles() as $role)
                <flux:radio
                    value="{{ $role->value }}"
                    label="{{ $role->label() }}"
                    description="{{ $role->description() }}"/>
            @endforeach
        </flux:radio.group>
        blade;
    }
}
