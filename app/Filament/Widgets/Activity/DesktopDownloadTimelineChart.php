<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Activity;

use App\Core\Actions\DesktopUpdateMetrics;
use App\Core\Enums\DesktopUpdateEventType;
use Filament\Widgets\ChartWidget;

class DesktopDownloadTimelineChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Desktop Downloads & Installs';

    protected ?string $pollingInterval = '30s';

    protected ?string $maxHeight = '260px';

    public ?string $filter = 'this_month';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return ActivityTimelineChart::RANGE_OPTIONS;
    }

    public function mount(): void
    {
        $this->filter = $this->normalizeRange($this->filter);

        parent::mount();
    }

    public function updatedFilter(?string $range): void
    {
        $this->filter = $this->normalizeRange($range);

        $this->dispatch(ActivityTimelineChart::RANGE_CHANGED_EVENT, range: $this->filter);
    }

    protected function getData(): array
    {
        $series = app(DesktopUpdateMetrics::class)->series($this->filter ?? 'this_month', [
            DesktopUpdateEventType::DownloadRedirected,
            DesktopUpdateEventType::UpdateInstalled,
        ]);

        if ($series['labels'] === []) {
            return [
                'datasets' => [
                    [
                        'label' => 'Downloads',
                        'data' => [0],
                    ],
                    [
                        'label' => 'Installs',
                        'data' => [0],
                    ],
                ],
                'labels' => ['No data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Downloads',
                    'data' => $series['datasets'][DesktopUpdateEventType::DownloadRedirected->value] ?? [],
                    'borderColor' => '#1d4ed8',
                    'backgroundColor' => 'rgba(29, 78, 216, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Installs',
                    'data' => $series['datasets'][DesktopUpdateEventType::UpdateInstalled->value] ?? [],
                    'borderColor' => '#15803d',
                    'backgroundColor' => 'rgba(21, 128, 61, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $series['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function normalizeRange(?string $range): string
    {
        if (is_string($range) && array_key_exists($range, ActivityTimelineChart::RANGE_OPTIONS)) {
            return $range;
        }

        return 'this_month';
    }
}
