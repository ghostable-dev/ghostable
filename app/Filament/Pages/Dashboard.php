<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DeviceStats;
use App\Filament\Widgets\InquiryStats;
use App\Filament\Widgets\MailingListEmailStats;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\WidgetConfiguration;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return array_values(array_filter(
            parent::getWidgets(),
            function (string|WidgetConfiguration $widget): bool {
                return ! in_array($this->normalizeWidgetClass($widget), [
                    DeviceStats::class,
                    InquiryStats::class,
                    MailingListEmailStats::class,
                ], true);
            },
        ));
    }

    protected function normalizeWidgetClass(string|WidgetConfiguration $widget): string
    {
        if ($widget instanceof WidgetConfiguration) {
            return $widget->widget;
        }

        return $widget;
    }
}
