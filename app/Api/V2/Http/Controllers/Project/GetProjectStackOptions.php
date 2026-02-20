<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Project;

use App\Core\Http\Controllers\Controller;
use App\Project\Enums\DeploymentProvider;
use App\Project\Enums\ProjectStackTag;
use App\Project\Support\ProjectStackOptions;
use Illuminate\Http\JsonResponse;

final class GetProjectStackOptions extends Controller
{
    public function __invoke(): JsonResponse
    {
        $languages = array_map(
            static fn (ProjectStackTag $tag) => [
                'value' => $tag->value,
                'label' => $tag->label(),
            ],
            ProjectStackTag::languageTags()
        );

        $frameworks = [];
        $platforms = [];

        foreach (ProjectStackTag::languageTags() as $language) {
            $languageKey = $language->value;

            $frameworks[$languageKey] = array_map(
                static fn (ProjectStackTag $tag) => [
                    'value' => $tag->value,
                    'label' => $tag->label(),
                ],
                ProjectStackOptions::frameworksFor($languageKey)
            );

            $platforms[$languageKey] = array_map(
                static fn (ProjectStackTag $tag) => [
                    'value' => $tag->value,
                    'label' => $tag->label(),
                ],
                ProjectStackOptions::platformsFor($languageKey)
            );
        }

        $providers = array_map(
            static fn (DeploymentProvider $provider) => [
                'value' => $provider->value,
                'label' => $provider->label(),
            ],
            DeploymentProvider::cases()
        );

        return response()->json([
            'data' => [
                'languages' => $languages,
                'frameworks' => $frameworks,
                'platforms' => $platforms,
                'providers' => $providers,
            ],
        ]);
    }
}
