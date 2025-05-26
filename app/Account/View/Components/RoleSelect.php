<?php

namespace App\Account\View\Components;

use App\Account\Managers\ACLManager;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RoleSelect extends Component
{
    public function __construct()
    {}
    
    public function roles(): array
    {
        return collect(ACLManager::getRoles())
            ->reject(fn ($role) => $role->key === 'custom')
            ->toArray();
    }
    
    public function render(): View|Closure|string
    {
        return <<<'blade'
        <flux:radio.group {{ $attributes }} label="Role">
            @foreach($roles() as $role)
                <flux:radio
                    value="{{ $role->key }}"
                    label="{{ $role->name }}"
                    description="{{ $role->description }}"/>
            @endforeach
        </flux:radio.group>
        blade;
    }
}
