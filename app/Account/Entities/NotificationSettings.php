<?php

namespace App\Account\Entities;

use App\Core\Enums\NotificationCategory;
use Spatie\LaravelData\Data;

class NotificationSettings extends Data
{
    /** @param array<string,bool> $preferences */
    public function __construct(
        public array $preferences = []
    ) {}

    public static function defaults(): self
    {
        $prefs = collect(NotificationCategory::cases())
            ->mapWithKeys(fn ($case) => [$case->value => true])
            ->all();

        return new self($prefs);
    }

    public function enabled(NotificationCategory $category): bool
    {
        return $this->preferences[$category->value] ?? true;
    }
}
