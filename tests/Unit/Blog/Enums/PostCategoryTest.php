<?php

use App\Blog\Enums\PostCategory;

it('returns labels and select options for all categories', function () {
    $options = PostCategory::selectOptions();

    foreach (PostCategory::cases() as $case) {
        expect($case->label())->toBe($options[$case->value]);
    }

    expect($options)->toHaveCount(count(PostCategory::cases()));
});
