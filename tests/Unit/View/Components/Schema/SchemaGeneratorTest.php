<?php

use App\View\Components\Schema\SchemaGenerator;
use Spatie\SchemaOrg\Schema;
use Tests\TestCase;

uses(TestCase::class);

class DummySchema extends SchemaGenerator
{
    public function __construct()
    {
        $this->type = Schema::thing();
    }

    public function organizationScript(): string
    {
        return $this->defaultOrganization()->toScript();
    }
}

it('renders schema script', function () {
    $component = new DummySchema;

    expect($component->render())->toContain('<script type="application/ld+json"');
});

it('returns default organization schema', function () {
    $component = new DummySchema;

    expect($component->organizationScript())->toContain('Ghostable');
});
