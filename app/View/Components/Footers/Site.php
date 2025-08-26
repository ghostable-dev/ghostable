<?php

namespace App\View\Components\Footers;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Site extends Component
{
    public function socialLinks(): array
    {
        return [
            // $this->makeSocialLink(
            //     url: config('contact.social.facebook'),
            //     name: 'Facebook',
            //     icon: 'icons.facebook'
            // ),
            $this->makeSocialLink(
                url: config('contact.social.instagram'),
                name: 'Instagram',
                icon: 'icons.instagram'
            ),
            $this->makeSocialLink(
                url: config('contact.social.x'),
                name: 'X',
                icon: 'icons.x'
            ),
            $this->makeSocialLink(
                url: config('contact.social.youtube'),
                name: 'YouTube',
                icon: 'icons.youtube'
            ),
            $this->makeSocialLink(
                url: config('contact.social.linkedin'),
                name: 'LinkedIn',
                icon: 'icons.linkedin'
            ),
        ];
    }

    public function makeSocialLink(string $url, string $name, string $icon): array
    {
        return compact('url', 'name', 'icon');
    }

    public function resourceLinks(): array
    {
        return [
            $this->makeLink(url: route('search'), label: 'Search Jobs'),
            $this->makeLink(url: route('pricing'), label: 'Pricing'),
            $this->makeLink(url: route('blog'), label: 'Blog'),
            $this->makeLink(url: config('contact.support.url'), label: 'Support', target: '_blank'),
        ];
    }

    public function companyLinks(): array
    {
        return [
            // $this->makeLink(url: route('contact'), label: 'Contact'),
            // $this->makeLink(url: route('careers'), label: 'Careers'),
            $this->makeLink(url: route('terms'), label: 'Terms'),
            $this->makeLink(url: route('privacy'), label: 'Privacy'),
        ];
    }

    public function makeLink(string $url, string $label, string $target = '_self'): array
    {
        return compact('url', 'label', 'target');
    }

    public function render(): View|Closure|string
    {
        return view('components.footers.site');
    }
}
