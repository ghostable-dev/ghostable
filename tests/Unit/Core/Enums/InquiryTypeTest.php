<?php

use App\Core\Enums\InquiryType;
use Tests\TestCase;

uses(TestCase::class);

it('provides options mapping', function () {
    expect(InquiryType::options())->toBe([
        'sales' => 'Sales',
        'support' => 'Support',
        'partnership' => 'Partnership',
        'other' => 'Other',
    ]);
});

it('returns label for each case', function () {
    expect(InquiryType::SALES->label())->toBe('Sales')
        ->and(InquiryType::SUPPORT->label())->toBe('Support')
        ->and(InquiryType::PARTNERSHIP->label())->toBe('Partnership')
        ->and(InquiryType::OTHER->label())->toBe('Other');
});
