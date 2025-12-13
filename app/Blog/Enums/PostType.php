<?php

namespace App\Blog\Enums;

enum PostType: string
{
    case ARTICLE = 'article';
    case INSIGHT = 'insight';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::ARTICLE => 'Article',
            self::INSIGHT => 'Insight',
        };
    }

    public function is(self $type): bool
    {
        return $this === $type;
    }
}
