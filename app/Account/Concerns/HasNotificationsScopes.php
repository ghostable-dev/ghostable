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
     * Read users.notifications.preferences[$key] as a boolean.
     * Works even if notifications/preferences/key is missing or NULL.
     *
     * MySQL 8+: JSON_UNQUOTE(JSON_EXTRACT(...)) returns 'true'/'false' strings.
     */
    public function withPreferenceEnabled(NotificationCategory $category): Builder
    {
        // Path like $.preferences.blog (properly quoted)
        $jsonPath = $this->jsonPath("preferences.$category->value");

        // COALESCE to default when JSON_EXTRACT is NULL (missing key or null value)
        $sql = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`users`.`notifications`, ?)), ?) = 'true'";

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
