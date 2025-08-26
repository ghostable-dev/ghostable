<?php

namespace App\View\Components\Sidebars;

use App\Account\Managers\AccountSwitcher;
use App\Account\Models\Account;
use App\Core\Concerns\MakesLinks;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class OrganizationSettings extends Component
{
    use MakesLinks;

    public function primaryLinks(): array
    {
        if (is_null($account = $this->getAccount())) {
            return [];
        }

        return [
            $this->makeLink(
                url: route('account.organization.overview', $account->id),
                label: 'Overview',
                icon: 'icons.building-office',
                active: $this->isRouteNameCurrent('account.organization.overview')
            ),
            $this->makeLink(
                url: route('account.organization.branding', $account->id),
                label: 'Branding',
                icon: 'icons.paint-brush',
                active: $this->isRouteNameCurrent('account.organization.branding')
            ),
        ];
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
