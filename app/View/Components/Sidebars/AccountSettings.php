<?php

namespace App\View\Components\Sidebars;

use App\Account\Managers\AccountSwitcher;
use App\Account\Models\Account;
use App\Core\Concerns\MakesLinks;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Laravel\Pennant\Feature;

class AccountSettings extends Component
{
    use MakesLinks;
    
    public function primaryLinks(): array
    {
        if (is_null($account = $this->getAccount())) {
            return [];
        }
        
        $links = [
            $this->makeLink(
                url: route('account.settings.billing', $this->getAccount()->id), 
                label: 'Billing',
                icon: 'icons.credit-card',
                active: $this->isRouteNameCurrent('account.settings.billing')
            )
        ];
        
        if (Feature::for($this->getAccount())->active('integrations')) {
            $links[] = $this->makeLink(
                url: route('account.settings.integrations', $this->getAccount()->id), 
                label: 'Integrations',
                icon: 'icons.squares-plus',
                active: $this->isRouteNameCurrent('account.settings.integrations')
            );
        }
        
        return $links;
    }
    
    protected function getAccount(): ?Account
    {
        if (auth()->user()->isAccountHolder()) {
            return auth()->user()->primaryAccount;
        }
        
        return AccountSwitcher::get();
    }
    
    public function render(): View|Closure|string
    {
        return view('components.sidebars.account-settings');
    }
}