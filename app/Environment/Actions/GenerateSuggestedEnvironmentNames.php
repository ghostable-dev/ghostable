<?php

namespace App\Environment\Actions;

use App\Environment\Enums\EnvironmentType;
use App\Project\Models\Project;

class GenerateSuggestedEnvironmentNames
{
    protected const PERSONALIZED_TYPES = [
        EnvironmentType::LOCAL,
        EnvironmentType::DEVELOPMENT,
        EnvironmentType::SANDBOX,
        EnvironmentType::TESTING,
        EnvironmentType::PREVIEW,
        EnvironmentType::INTEGRATION,
        EnvironmentType::OTHER,
    ];

    public static function handle(
        Project $project,
        EnvironmentType $type,
        int $maxSuggestions = 5,
        int $suffixLimit = 20
    ): array {
        $baseSlug = $type->slug();
        $suggestions = [];

        // Start with the base slug itself if available
        if (!$project->environments()->where('name', $baseSlug)->exists()) {
            $suggestions[] = $baseSlug;
        }

        // Only personalize certain types
        if (in_array($type, self::PERSONALIZED_TYPES)) {
            $memberNames = $project->team
                ->users()
                ->get()
                ->map(fn ($user) => $user->initials())
                ->unique()
                ->filter()
                ->map(fn ($name) => str($name)->slug()->toString())
                ->values();

            foreach ($memberNames as $memberSlug) {
                $combinedSlug = "{$baseSlug}-{$memberSlug}";
                if (!$project->environments()->where('name', $combinedSlug)->exists()) {
                    $suggestions[] = $combinedSlug;
                }
                if (count($suggestions) >= $maxSuggestions) {
                    break;
                }
            }
        }

        // Add numeric fallbacks if suggestions are still below limit
        $count = 1;
        while (count($suggestions) < $maxSuggestions && $count <= $suffixLimit) {
            $numberedSlug = "{$baseSlug}-{$count}";
            if (!$project->environments()->where('name', $numberedSlug)->exists()) {
                $suggestions[] = $numberedSlug;
            }
            $count++;
        }

        return $suggestions;
    }
}