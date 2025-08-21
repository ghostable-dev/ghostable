<?php

namespace App\View\Components\Headers;

use App\Core\Concerns\MakesLinks;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Site extends Component
{
    use MakesLinks;
    
    public function primaryLinks(): array
    {
        return [
            $this->makeLink(
                url: route('search'), 
                label: 'Jobs',
                active: $this->isRouteNameCurrent('search')
            ),
            $this->makeLink(
                url: route('pricing'), 
                label: 'Pricing',
                active: $this->isRouteNameCurrent('pricing')
            ),
            $this->makeLink(
                url: route('blog'), 
                label: 'Blog',
                active: $this->isRouteNameCurrent('blog')
            )
        ];
    }

    public function render(): View|Closure|string
    {
        return view('components.headers.site');
    }
}
