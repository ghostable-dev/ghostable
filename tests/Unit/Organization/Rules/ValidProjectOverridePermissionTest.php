<?php

use App\Organization\Rules\ValidProjectOverridePermission;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

it('passes when value is an OrganizationPermission enum instance', function () {
    $validator = Validator::make(
        ['permission' => OrganizationPermission::ManageProjectSettings],
        ['permission' => [new ValidProjectOverridePermission()]]
    );

    expect($validator->passes())->toBeTrue();
});

it('passes when value is a valid project override permission string', function () {
    $validator = Validator::make(
        ['permission' => OrganizationPermission::ManageProjectSettings->value],
        ['permission' => [new ValidProjectOverridePermission()]]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails when value is not a valid project override permission', function () {
    $invalid = OrganizationPermission::ManageOrganizationMembers->value;

    $validator = Validator::make(
        ['permission' => $invalid],
        ['permission' => [new ValidProjectOverridePermission()]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('permission'))
        ->toBe("The selected permission [{$invalid}] is not a valid project override permission.");
});

