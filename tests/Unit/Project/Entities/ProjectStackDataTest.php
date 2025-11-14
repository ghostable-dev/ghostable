<?php

use App\Project\Entities\ProjectStackData;
use App\Project\Enums\ProjectStackTag;
use App\Project\Models\Project;
use Tests\TestCase;

uses(TestCase::class);

it('filters null values when converting to array', function () {
    $data = new ProjectStackData(
        language: ProjectStackTag::LanguagePHP,
        framework: null,
        platform: ProjectStackTag::PlatformLaravelVapor
    );

    expect($data->toArray())->toBe([
        'language' => ProjectStackTag::LanguagePHP->value,
        'platform' => ProjectStackTag::PlatformLaravelVapor->value,
    ]);
});

it('casts project stack attributes to the data object', function () {
    $project = new Project([
        'stack' => [
            'language' => ProjectStackTag::LanguagePHP->value,
            'framework' => ProjectStackTag::FrameworkLaravel->value,
        ],
    ]);

    $stack = $project->stack;

    expect($stack)
        ->toBeInstanceOf(ProjectStackData::class)
        ->language->toBe(ProjectStackTag::LanguagePHP)
        ->framework->toBe(ProjectStackTag::FrameworkLaravel)
        ->platform->toBeNull();
});
