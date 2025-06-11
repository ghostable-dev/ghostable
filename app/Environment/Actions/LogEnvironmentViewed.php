<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use App\Account\Models\User;
use Spatie\Activitylog\Models\Activity;

class LogEnvironmentViewed
{
    /**
     * Log that a user accessed the environment variable manager UI.
     *
     * This logs a single `viewed` event on the environment itself, 
     * using Spatie activity log. It is deduplicated by environment,
     * user, and source within a cooldown window.
     */
    public function handle(
        Environment $environment, 
        User $user, 
        string $source = 'ui', 
        int $cooldownMinutes = 30
    ): void
    {
        $recentlyLogged = Activity::query()
            ->where('subject_type', $environment->getMorphClass())
            ->where('subject_id', $environment->id)
            ->where('causer_id', $user->id)
            ->where('event', 'viewed')
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->where('properties->source', $source)
            ->exists();

        if ($recentlyLogged) {
            return;
        }

        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event('viewed')
            ->withProperties([
                'source' => $source,
            ])->log("Viewed environment variables for \"{$environment->name}\" via {$source}");
    }
}