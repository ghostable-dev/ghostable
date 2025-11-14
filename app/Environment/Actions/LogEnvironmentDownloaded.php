<?php

namespace App\Environment\Actions;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Support\EnvironmentAuditProperties;

class LogEnvironmentDownloaded
{
    /**
     * Log that a user downloaded or pulled the environment file.
     *
     * @param  array<string, mixed>  $context  Additional metadata to merge into the activity properties.
     */
    public function handle(Environment $environment, User $user, string $source = 'ui', array $context = []): void
    {
        $description = $context['description'] ?? $this->defaultDescription($environment, $source);
        $event = $context['event'] ?? $this->eventNameForSource($source);
        $ipAddress = $context['ip_address'] ?? request()?->ip();
        unset($context['description'], $context['event'], $context['ip_address']);

        $properties = $context;
        $properties['source'] = $source;
        $properties['environment'] = EnvironmentAuditProperties::make($environment);
        $properties['requested_by'] = [
            'id' => (string) $user->id,
            'email' => $user->email,
        ];
        $properties['ip_address'] = $ipAddress;

        activity('variable')
            ->performedOn($environment)
            ->causedBy($user)
            ->event($event)
            ->withProperties($properties)
            ->log($description);
    }

    protected function defaultDescription(Environment $environment, string $source): string
    {
        if ($source === 'cli') {
            return "Pulled '{$environment->name}' environment via {$source}.";
        }

        return "Downloaded environment file for \"{$environment->name}\" via {$source}";
    }

    protected function eventNameForSource(string $source): string
    {
        return $source === 'cli' ? 'pulled' : 'downloaded';
    }
}
