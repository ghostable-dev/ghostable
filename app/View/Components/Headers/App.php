<?php

namespace App\View\Components\Headers;

use App\Account\Enums\AccountType;
use App\Account\Managers\AccountSwitcher;
use App\Account\Models\Account;
use App\Account\Models\User;
use App\Core\Concerns\MakesLinks;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class App extends Component
{
    use MakesLinks;
    
    public function primaryLinks(): array
    {
        return $this->for(auth()->user());
    }
    
    public function for(User $user): array
    {
        if ($user->isCandidate() && !$user->isFounder()) {
            return [
                $this->makeLink(
                    url: route('candidate.home'), 
                    label: 'Matches',
                    active: $this->isRouteNameCurrent('candidate.home')
                ),
                $this->makeLink(
                    url: route('candidate.jobs'), 
                    label: 'Search',
                    active: $this->isRouteNameCurrent('candidate.jobs')
                ),
                $this->makeLink(
                    url: route('candidate.bookmarks'), 
                    label: 'Bookmarks',
                    active: $this->isRouteNameCurrent('candidate.bookmarks')
                ),
                $this->makeLink(
                    url: route('candidate.preferences.general'), 
                    label: 'Preferences',
                    active: $this->isRouteNameCurrent('candidate.preferences.*')
                )
            ];
        }
        
        if ($user->isAccountHolder()) {
            return match ($user->primaryAccount->type) {
                AccountType::ORG => $this->orgLinks(user: $user, account: $user->primaryAccount),
                AccountType::EDU => [],
                AccountType::REC => [],
                default => [null],
            };
        }
        
        if ($user->isFounder()) {
            $account = AccountSwitcher::get();
            return match ($account?->type) {
                AccountType::ORG => $this->orgLinks(user: $user, account: $account),
                AccountType::EDU => [],
                AccountType::REC => [],
                default => [],
            };
        }
        
        return [];
    }
    
    public function orgLinks(User $user, Account $account): array
    {
        $isAdmin = $user->isFounder() || $user->primaryRoleIsAdmin();
        $links = [];

        if ($isAdmin || $user->primaryRoleIsJobManager()) {
            $link = $this->makeLink(
                url: route('account.jobs', $account->id), 
                label: 'My Jobs',
                active: $this->isRouteNameCurrent('account.jobs')
            );
            array_push($links, $link);
        }
        
        if ($isAdmin) {
            $link = $this->makeLink(
                url: route('account.team', $account->id), 
                label: 'Team',
                active: $this->isRouteNameCurrent('account.team')
            );
            array_push($links, $link);
        }
        
        if ($isAdmin || $user->primaryRoleIsBillingManager()) {
            $link = $this->makeLink(
                url: route('account.organization.overview', $account->id), 
                label: 'Organization',
                active: $this->isRouteNameCurrent('account.organization.overview')
            );
            array_push($links, $link);
            $link = $this->makeLink(
                url: route('account.settings.billing', $account->id), 
                label: 'Settings',
                active: $this->isRouteNameCurrent('account.settings.billing')
            );
            array_push($links, $link);
        }
        
        return $links;
    }

    public function render(): View|Closure|string
    {
        return view('components.headers.app');
    }
}
