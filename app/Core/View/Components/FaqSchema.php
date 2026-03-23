<?php

namespace App\Core\View\Components;

use Spatie\SchemaOrg\Schema;

class FaqSchema extends SchemaGenerator
{
    /**
     * @param  array<int, array{question: string, answer: string}>  $items
     */
    public function __construct(public array $items)
    {
        $questions = collect($items)
            ->filter(fn (array $item): bool => filled($item['question'] ?? null) && filled($item['answer'] ?? null))
            ->values()
            ->map(function (array $item) {
                $answer = str_replace(['<br>', '<br/>', '<br />'], ' ', (string) $item['answer']);

                return Schema::question()
                    ->name(trim(strip_tags((string) $item['question'])))
                    ->acceptedAnswer(
                        Schema::answer()->text(trim(strip_tags($answer)))
                    );
            })
            ->all();

        $this->type = Schema::faqPage()->mainEntity($questions);
    }
}
