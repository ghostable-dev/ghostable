<?php

namespace App\Account\Enums;

enum MailingListEmailSource: string
{
    case BLOG = 'blog';
    case INTEGRATION = 'integration';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($source) => [$source->value => $source->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::BLOG => 'Blog',
            self::INTEGRATION => 'Integration',
        };
    }

    public function is(self $source): bool
    {
        return $this === $source;
    }
}
