<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows desktop license plans without authentication', function (): void {
    $this->get(route('licenses'))
        ->assertSuccessful()
        ->assertViewIs('site.licenses')
        ->assertSeeText('Choose your Ghostable Desktop license')
        ->assertSeeText('Personal')
        ->assertSeeText('Team 5')
        ->assertSeeText('Team 10')
        ->assertSeeText('$49')
        ->assertSeeText('$249')
        ->assertSeeText('$499')
        ->assertSeeText('No Ghostable account required')
        ->assertSee(route('licenses.checkout.start', ['plan' => 'personal']), escape: false)
        ->assertSee(route('login'), escape: false);
});

it('sends authenticated users to billing to purchase a license', function (): void {
    $user = $this->createUser('License Buyer', 'license-page-buyer@example.com');

    $this->actingAs($user)
        ->get(route('licenses'))
        ->assertSuccessful()
        ->assertSee(route('organization.settings.billing'), escape: false)
        ->assertSeeText('Choose Personal')
        ->assertDontSeeText('Create account to buy');
});
