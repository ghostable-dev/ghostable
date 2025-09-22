<?php

namespace App\Account\Concerns;

use App\Core\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Builder;

trait HasNotificationsScopes
{
    public function receivesBlogNotifications(): Builder
    {
        return $this->withPreferenceEnabled(NotificationCategory::BLOG);
    }

    public function receivesResearchNotifications(): Builder
    {
        return $this->withPreferenceEnabled(NotificationCategory::RESEARCH);
    }

    public function receivesPromotionalNotifications(): Builder
    {
        return $this->withPreferenceEnabled(NotificationCategory::PROMOTIONAL);
    }

    public function receivesProductTips(): Builder
    {
        return $this->withPreferenceEnabled(NotificationCategory::PRODUCT_TIPS);
    }

    /**
     * Read notifications.preferences[$key] as a boolean.
     * Works even if the key is missing or NULL.
     */
    public function withPreferenceEnabled(NotificationCategory $category): Builder
    {
        $table = $this->getModel()->getTable();

        // Path like $.preferences.blog
        $jsonPath = $this->jsonPath("preferences.{$category->value}");

        $sql = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`{$table}`.`notifications`, ?)), ?) = 'true'";

        return $this->whereRaw($sql, [$jsonPath, 'true']);
    }

    /**
     * Build a safe JSON path: "a.b" -> $.\"a\".\"b\"
     * (quoted segments prevent surprises with special chars).
     */
    protected function jsonPath(string $dotPath): string
    {
        $segments = array_map(
            fn ($s) => str_replace('"', '\"', $s),
            explode('.', $dotPath)
        );

        return '$.'.implode('.', array_map(fn ($s) => "\"{$s}\"", $segments));
    }
}
