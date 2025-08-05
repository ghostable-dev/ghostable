<?php

namespace App\Environment\Builders;

use App\Environment\Models\Environment;
use Illuminate\Database\Eloquent\Builder;

class EnvironmentVariableBuilder extends Builder
{
    public function overrides(): Builder
    {
        return $this->where('is_override', true);
    }

    public function visible(): Builder
    {
        return $this->where('is_commented', false);
    }

    public function commented(): Builder
    {
        return $this->where('is_commented', true);
    }

    public function forEnvironment(string|Environment $environment): Builder
    {
        $environmentId = $environment instanceof Environment
            ? $environment->id
            : $environment;

        return $this->where('environment_id', $environmentId);
    }

    public function key(string $key): Builder
    {
        return $this->where('key', $key);
    }

    public function recent(int $days = 7): Builder
    {
        $after = now()->subDays($days);
        
        return $this->where('last_updated_at', '>=', $after);
    }

    public function withLatestVersion(): Builder
    {
        return $this->with('latestVersion');
    }
}