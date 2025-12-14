<?php

namespace App\Core\View\Components;

use Spatie\SchemaOrg\Schema;

class BreadcrumbSchema extends SchemaGenerator
{
    /**
     * @param  array<int, array{name: string, item: string}>  $items
     */
    public function __construct(public array $items)
    {
        $listItems = collect($items)
            ->values()
            ->map(function (array $item, int $index) {
                return Schema::listItem()
                    ->position($index + 1)
                    ->name($item['name'])
                    ->item($this->absoluteUrl($item['item']));
            })
            ->all();

        $this->type = Schema::breadcrumbList()->itemListElement($listItems);
    }
}
