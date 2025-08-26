<?php

namespace App\View\Components;

use App\Core\Concerns\MakesLinks;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Header extends Component
{
    use MakesLinks;

    public function primaryLinks(): array
    {
        return array_merge($this->accountLinks(), [
            $this->makeLink(url: route('home'), label: 'Jobs'),
            $this->makeLink(url: route('pricing'), label: 'Pricing'),
            $this->makeLink(url: route('blog'), label: 'Blog'),
        ]);
    }

    private function accountLinks(): array
    {
        if (! auth()->user()?->isOrganization()) {
            return [];
        }

        return [
            $this->makeLink(
                url: route('account.jobs', auth()->user()->primaryAccount->id),
                label: 'My Jobs'
            ),
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.header');
    }
}
