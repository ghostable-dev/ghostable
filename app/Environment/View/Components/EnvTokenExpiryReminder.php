<?php

namespace App\Environment\View\Components;

use App\Auth\Models\PersonalAccessToken;
use App\Environment\Models\Environment;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class EnvTokenExpiryReminder extends Component
{ 
    public Environment $environment;
    
    public function __construct(public PersonalAccessToken $token)
    {
        $this->environment = $token->tokenable;
    }
    
    public function render(): View|Closure|string
    {
        return <<<'blade'
            <x-calendar-reminder 
                {{ $attributes }}
                label="Add Expiry Reminder"
                :expiry="$token->expires_at" 
                title="Rotate Ghostable CI/CD Key" 
                description="{{ $description }}"/>
        blade;
    }

    public function description(): string
    {
        return <<<TEXT
            The access token named {$this->label()} inside the {$this->environment->project->name} project of Ghostable (https://ghostable.dev)
            will expire on {$this->token->expires_at->timezone(timezone())->format(DT_FORMAT)}.
            
            To prevent CI/CD issues you should...
            • Generate a replacement token  
            • Update you any relavant CI/CD deployment scripts
            • Remove this token after the new one is live
        TEXT;
    }
    
    public function label(): string
    {
        return "{$this->token->name} (ending in {$this->token->token_suffix})";
    }
}
