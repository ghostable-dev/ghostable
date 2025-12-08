<?php

namespace App\Core\Actions;

use App\Account\Models\User;
use App\Environment\Models\DeploymentToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamActivityCsv
{
    /**
     * Stream a CSV export for the provided activity query.
     */
    public function handle(Builder $query, string $filename, array $context = []): StreamedResponse
    {
        $context = array_merge([
            'project_name' => null,
            'project_id' => null,
            'environment_id' => null,
        ], $context);

        return response()->streamDownload(function () use ($query, $context) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'Project',
                'Project ID',
                'Environment ID',
                'Event',
                'Subject',
                'User',
                'Source',
                'Time',
            ]);

            foreach ($query->lazy() as $activity) {
                fputcsv($output, [
                    $context['project_name'],
                    $context['project_id'],
                    $context['environment_id'],
                    ucfirst((string) $activity->event),
                    $activity->subject_type,
                    $this->formatCauser($activity->causer),
                    $activity->description,
                    optional($activity->created_at)
                        ? optional($activity->created_at)->timezone(timezone())->toIso8601String()
                        : '',
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function formatCauser(?Model $causer): string
    {
        if ($causer instanceof User) {
            return $causer->email;
        }

        if ($causer instanceof DeploymentToken) {
            return 'Deployment token: '.Str::of($causer->name)->trim();
        }

        return 'System';
    }
}
