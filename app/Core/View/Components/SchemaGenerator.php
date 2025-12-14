<?php

namespace App\Core\View\Components;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\Type;

abstract class SchemaGenerator extends Component
{
    protected ?Type $type = null;

    protected function absoluteUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : url($path);
    }

    protected function defaultOrganization(): Organization
    {
        $organization = Schema::organization()
            ->name(config('app.name', 'Ghostable'))
            ->legalName(config('contact.legalName', 'Ghostable, LLC'))
            ->telephone(config('contact.phone'))
            ->address(
                Schema::postalAddress()
                    ->streetAddress(config('contact.address.line1'))
                    ->addressLocality(config('contact.address.addressLocality'))
                    ->addressRegion(config('contact.address.addressRegion'))
                    ->postalCode(config('contact.address.postalCode'))
                    ->addressCountry(config('contact.address.addressCountry'))
            )->founders([
                Schema::person()
                    ->name('Joe Rucci')
                    ->email('joe@ghostable.dev'),
            ])
            ->sameAs(array_filter([
                config('contact.social.github'),
                config('contact.social.discord'),
                config('contact.social.linkedin'),
                config('contact.social.x'),
                config('contact.social.youtube'),
            ]))
            ->url(url('/'))
            ->logo($this->absoluteUrl('/images/logo-dark.svg'));

        if ($foundingYear = config('contact.founding_year')) {
            $organization->foundingDate($foundingYear);
        }

        return $organization;
    }

    public function render(): HtmlString
    {
        return new HtmlString($this->type?->toScript() ?? '');
    }
}
