<?php

use App\Billing\Enums\Plan;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Config::set('platform.billing', [
        Plan::STANDARD->value => ['type' => Plan::STANDARD->value, 'api_id' => 'std'],
        Plan::SCALE->value => ['type' => Plan::SCALE->value, 'api_id' => 'scl'],
    ]);
});

it('provides select options', function () {
    expect(Plan::selectOptions())->toBe([
        'free' => 'Free',
        'standard' => 'Standard',
        'scale' => 'Scale',
        'enterprise' => 'Enterprise',
    ]);
});

it('provides select options for billable plans only', function () {
    expect(Plan::selectOptions(true))->toBe([
        'standard' => 'Standard',
        'scale' => 'Scale',
    ]);
});

it('returns all billable plans', function () {
    expect(Plan::billable())->toEqual([Plan::STANDARD, Plan::SCALE]);
});

it('resolves plan from billable id', function () {
    expect(Plan::tryFromBillableId('scl'))->toBe(Plan::SCALE)
        ->and(Plan::tryFromBillableId('std'))->toBe(Plan::STANDARD)
        ->and(Plan::tryFromBillableId('unknown'))->toBeNull();
});

it('provides labels', function () {
    expect(Plan::FREE->label())->toBe('Free')
        ->and(Plan::STANDARD->label())->toBe('Standard')
        ->and(Plan::SCALE->label())->toBe('Scale')
        ->and(Plan::ENTERPRISE->label())->toBe('Enterprise');
});

it('gets billable ids and checks billable status', function () {
    expect(Plan::STANDARD->getBillableId())->toBe('std')
        ->and(Plan::SCALE->getBillableId())->toBe('scl')
        ->and(Plan::FREE->getBillableId())->toBeNull()
        ->and(Plan::ENTERPRISE->getBillableId())->toBeNull();

    expect(Plan::STANDARD->isBillable())->toBeTrue()
        ->and(Plan::SCALE->isBillable())->toBeTrue()
        ->and(Plan::FREE->isBillable())->toBeFalse()
        ->and(Plan::ENTERPRISE->isBillable())->toBeFalse();
});

it('checks plan helpers', function () {
    expect(Plan::FREE->isFree())->toBeTrue()
        ->and(Plan::STANDARD->isStandard())->toBeTrue()
        ->and(Plan::SCALE->isScale())->toBeTrue()
        ->and(Plan::ENTERPRISE->isEnterprise())->toBeTrue();
});

it('compares plans', function () {
    expect(Plan::FREE->is(Plan::FREE))->toBeTrue()
        ->and(Plan::FREE->is(Plan::STANDARD))->toBeFalse();
});
