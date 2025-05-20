<?php

namespace App\Account\Actions;

use App\Account\Models\Team;

class CreateNonConflictingSlug
{
    public static function handle(
        string $name,
        int $suffixLimit = 20,
        ?Team $existingTeam = null
    ): string {
        $base = str($name)->slug();
        $slug = $base;

        $count = 0;

        $slugTaken = Team::query()
            ->when(! is_null($existingTeam), function ($query) use ($existingTeam) {
                $query->where('id', '<>', $existingTeam->id);
            })->where('slug', $slug)
            ->exists();

        while ($slugTaken && $count < $suffixLimit) {
            $count++;
            $slug = str($base)->append('-')->append($count);
        }

        if ($count === $suffixLimit) {
            $slug = str()->uuid()->toString();
        }

        return $slug;
    }
}
