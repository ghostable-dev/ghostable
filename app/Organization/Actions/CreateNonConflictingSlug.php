<?php

namespace App\Organization\Actions;

use App\Organization\Models\Organization;

class CreateNonConflictingSlug
{
    public static function handle(
        string $name,
        int $suffixLimit = 20,
        ?Organization $existingOrganization = null
    ): string {
        $base = str($name)->slug();
        $slug = $base;

        $count = 0;

        $slugTaken = Organization::query()
            ->when(! is_null($existingOrganization), function ($query) use ($existingOrganization) {
                $query->where('id', '<>', $existingOrganization->id);
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
