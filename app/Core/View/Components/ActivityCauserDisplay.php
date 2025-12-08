<?php

namespace App\Core\View\Components;

use App\Account\Models\User;
use App\Environment\Models\DeploymentToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class ActivityCauserDisplay extends Component
{
    public string $type;

    public ?string $initials = null;

    public ?string $email = null;

    public ?string $tokenName = null;

    public function __construct(public ?Model $causer = null)
    {
        if ($causer instanceof User) {
            $this->type = 'user';
            $this->initials = $causer->initials();
            $this->email = $causer->email;
        } elseif ($causer instanceof DeploymentToken) {
            $this->type = 'token';
            $this->tokenName = $causer->name;
        } else {
            $this->type = 'system';
        }
    }

    public function render(): string
    {
        return <<<'blade'
            @if($type === 'user')
                <flux:profile
                    circle
                    :chevron="false"
                    size="xs"
                    :initials="$initials"
                    name="{{ $email }}"/>
            @elseif($type === 'token')
                <div class="flex items-center gap-2">
                    <flux:badge size="xs">Deployment token</flux:badge>
                    <span>{{ $tokenName }}</span>
                </div>
            @else
                System
            @endif
        blade;
    }
}
