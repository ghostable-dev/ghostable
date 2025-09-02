<?php

use App\Blog\Markdown\CustomConverter;

it('converts youtube embeds into iframes', function () {
    $converter = new CustomConverter();
    $html = (string) $converter->convert('@[youtube](https://example.com/embed/abc)');
    expect($html)->toContain('<iframe');
});
