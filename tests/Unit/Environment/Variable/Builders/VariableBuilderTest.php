<?php

use App\Environment\Models\Environment;
use App\Environment\Variable\Models\EnvironmentVariable;
use Tests\TestCase;

uses(TestCase::class);

it('adds where clause for overrides', function () {
    $where = EnvironmentVariable::query()->overrides()->getQuery()->wheres[0];

    expect($where['column'])->toBe('is_override')
        ->and($where['value'])->toBe(true);
});

it('adds where clause for visible variables', function () {
    $where = EnvironmentVariable::query()->visible()->getQuery()->wheres[0];

    expect($where['column'])->toBe('is_commented')
        ->and($where['value'])->toBe(false);
});

it('adds where clause for commented variables', function () {
    $where = EnvironmentVariable::query()->commented()->getQuery()->wheres[0];

    expect($where['column'])->toBe('is_commented')
        ->and($where['value'])->toBe(true);
});

it('scopes by environment instance and id', function () {
    $env = new Environment;
    $env->id = 'env1';

    $byModel = EnvironmentVariable::query()->forEnvironment($env)->getQuery()->wheres[0];
    $byId = EnvironmentVariable::query()->forEnvironment('env2')->getQuery()->wheres[0];

    expect($byModel['value'])->toBe('env1')
        ->and($byId['value'])->toBe('env2');
});

it('adds where clause for key', function () {
    $where = EnvironmentVariable::query()->key('AAA')->getQuery()->wheres[0];

    expect($where['column'])->toBe('key')
        ->and($where['value'])->toBe('AAA');
});

it('filters by recent date', function () {
    $where = EnvironmentVariable::query()->recent(7)->getQuery()->wheres[0];

    expect($where['column'])->toBe('last_updated_at')
        ->and($where['operator'])->toBe('>=');
});

it('eager loads latest version', function () {
    $query = EnvironmentVariable::query()->withLatestVersion();

    expect(array_keys($query->getEagerLoads()))->toContain('latestVersion');
});
