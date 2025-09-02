<?php

use App\Blog\Enums\PostStatus;

it('returns labels and select options for all statuses', function () {
    $options = PostStatus::selectOptions();

    foreach (PostStatus::cases() as $case) {
        expect($case->label())->toBe($options[$case->value]);
    }

    expect($options)->toHaveCount(count(PostStatus::cases()));
});

it('compares statuses correctly', function () {
    expect(PostStatus::PUBLISHED->is(PostStatus::PUBLISHED))->toBeTrue()
        ->and(PostStatus::PUBLISHED->is(PostStatus::DRAFT))->toBeFalse();
});
