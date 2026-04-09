<?php

namespace App\Core\View\Components;

use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Schema;

class PricingSchema extends SchemaGenerator
{
    /**
     * @param  array<int, array{
     *     name: string,
     *     heading_id: string,
     *     description: string,
     *     price: int|string
     * }>  $plans
     */
    public function __construct(
        public string $url,
        public array $plans,
    ) {
        $organization = $this->defaultOrganization();
        $pricingPage = Schema::webPage()
            ->name('Ghostable Pricing')
            ->description('Simple, transparent pricing for secure environment & secrets management.')
            ->url($url);

        $catalogItems = collect($plans)
            ->values()
            ->map(function (array $plan, int $index) use ($organization, $url) {
                $planUrl = "{$url}#{$plan['heading_id']}";

                return Schema::listItem()
                    ->position($index + 1)
                    ->name($plan['name'])
                    ->url($planUrl)
                    ->item(
                        Schema::offer()
                            ->name("Ghostable {$plan['name']} plan")
                            ->description($plan['description'])
                            ->price((string) $plan['price'])
                            ->priceCurrency('USD')
                            ->availability('https://schema.org/InStock')
                            ->url($planUrl)
                            ->itemOffered(
                                Schema::service()
                                    ->name("Ghostable {$plan['name']} plan")
                                    ->description($plan['description'])
                                    ->provider($organization)
                                    ->url($planUrl)
                            )
                    );
            })
            ->all();

        $this->type = (new Graph)
            ->add($pricingPage)
            ->add(
                Schema::service()
                    ->name('Ghostable')
                    ->description('Secure environment and secrets management for modern software teams.')
                    ->provider($organization)
                    ->url($url)
                    ->mainEntityOfPage($pricingPage)
                    ->hasOfferCatalog(
                        Schema::offerCatalog()
                            ->name('Ghostable pricing plans')
                            ->url($url)
                            ->numberOfItems(count($catalogItems))
                            ->itemListElement($catalogItems)
                    )
            );
    }
}
