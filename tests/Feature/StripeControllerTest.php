<?php

use App\Http\Controllers\StripeController;
use App\Services\StripeConnectService;
use App\Models\User;
use App\Models\Host;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('updates stripe account status to completed when requirements are met on onboarding return', function () {
    $user = User::factory()->create();
    $host = Host::factory()->create([
        'user_id'              => $user->id,
        'stripe_connect_id'    => 'acct_test123',
        'stripe_account_status'=> 'pending',
    ]);

    // Fake a Stripe account with no pending requirements.
    $fakeStripeAccount = (object)[
        'requirements' => (object)['current_deadline' => null],
    ];

    $stripeServiceMock = Mockery::mock(StripeConnectService::class);
    $stripeServiceMock->shouldReceive('retrieveAccount')
        ->once()
        ->with('acct_test123')
        ->andReturn($fakeStripeAccount);
    app()->instance(StripeConnectService::class, $stripeServiceMock);

    $response = $this->actingAs($user)->get(route('stripe.onboarding.return'));
    $response->assertRedirect(route('settings.bank-account'));

    $host->refresh();
    expect($host->stripe_account_status)->toEqual('completed');
});

it('updates stripe account status to incomplete when requirements are not met on onboarding return', function () {
    $user = User::factory()->create();
    $host = Host::factory()->create([
        'user_id'              => $user->id,
        'stripe_connect_id'    => 'acct_test123',
        'stripe_account_status'=> 'pending',
    ]);

    // Fake a Stripe account with a current_deadline (incomplete onboarding).
    $fakeStripeAccount = (object)[
        'requirements' => (object)['current_deadline' => '2025-01-01'],
    ];

    $stripeServiceMock = Mockery::mock(StripeConnectService::class);
    $stripeServiceMock->shouldReceive('retrieveAccount')
        ->once()
        ->with('acct_test123')
        ->andReturn($fakeStripeAccount);
    app()->instance(StripeConnectService::class, $stripeServiceMock);

    $response = $this->actingAs($user)->get(route('stripe.onboarding.return'));
    $response->assertRedirect(route('settings.bank-account'));

    $host->refresh();
    expect($host->stripe_account_status)->toEqual('incomplete');
});

it('redirects to a new onboarding URL on refresh', function () {
    $user = User::factory()->create();
    $host = Host::factory()->create([
        'user_id'           => $user->id,
        'stripe_connect_id' => 'acct_test123',
    ]);
    $fakeOnboardingUrl = 'https://stripe.com/onboarding/refresh';

    $stripeServiceMock = Mockery::mock(StripeConnectService::class);
    $stripeServiceMock->shouldReceive('startStripeConnectOnboarding')
        ->once()
        ->with($user)
        ->andReturn($fakeOnboardingUrl);
    app()->instance(StripeConnectService::class, $stripeServiceMock);

    $response = $this->actingAs($user)->get(route('stripe.onboarding.refresh'));
    $response->assertRedirect($fakeOnboardingUrl);
});
