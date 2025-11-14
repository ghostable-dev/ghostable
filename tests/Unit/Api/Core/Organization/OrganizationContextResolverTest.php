<?php

use App\Api\Core\Organization\OrganizationContextResolver;
use App\Environment\Models\Environment;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function requestWithParam($model): Request
{
    $request = Request::create('/');
    $route = new class($model)
    {
        public function __construct(private $model) {}

        public function parameters(): array
        {
            return ['model' => $this->model];
        }
    };
    $request->setRouteResolver(fn () => $route);

    return $request;
}

it('resolves organization from organization parameter', function () {
    $org = Organization::factory()->create();
    $resolver = new OrganizationContextResolver;

    $resolved = $resolver->resolveFromRequest(requestWithParam($org));
    expect($resolved->is($org))->toBeTrue();
});

it('resolves organization from project parameter', function () {
    $org = Organization::factory()->create();
    $project = Project::factory()->forOrganization($org)->create();
    $resolver = new OrganizationContextResolver;

    $resolved = $resolver->resolveFromRequest(requestWithParam($project));
    expect($resolved->is($org))->toBeTrue();
});

it('resolves organization from environment parameter', function () {
    $org = Organization::factory()->create();
    $project = Project::factory()->forOrganization($org)->create();
    $env = Environment::factory()->forProject($project)->create();
    $resolver = new OrganizationContextResolver;

    $resolved = $resolver->resolveFromRequest(requestWithParam($env));
    expect($resolved->is($org))->toBeTrue();
});

it('resolves organization from authenticated environment', function () {
    $org = Organization::factory()->create();
    $project = Project::factory()->forOrganization($org)->create();
    $env = Environment::factory()->forProject($project)->create();

    $request = Request::create('/');
    $request->setUserResolver(fn () => $env);

    $resolver = new OrganizationContextResolver;
    $resolved = $resolver->resolveFromRequest($request);

    expect($resolved->is($org))->toBeTrue();
});

it('returns null when organization cannot be resolved', function () {
    $resolver = new OrganizationContextResolver;
    $resolved = $resolver->resolveFromRequest(Request::create('/'));

    expect($resolved)->toBeNull();
});
