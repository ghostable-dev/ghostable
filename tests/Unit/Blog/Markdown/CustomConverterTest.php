<?php

use App\Blog\Markdown\CustomConverter;

it('converts markdown with custom extensions', function () {
    $converter = new CustomConverter;
    $html = (string) $converter->convert('~~strike~~');
    expect($html)->toContain('<del>strike</del>');
});
