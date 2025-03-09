<?php

use App\Services\StripeConnectService;
use App\Actions\StripeConnect\CreateStripeConnectAccountAction;
use App\Actions\StripeConnect\CreateAccountLinkAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

it('returns the onboarding URL', function () {
    $user = User::factory()->create();

    $fakeAccountId = 'acct_fake123';
    $fakeOnboardingUrl = 'https://stripe.com/onboarding';

    $createAccountAction = Mockery::mock(CreateStripeConnectAccountAction::class);
    $createAccountAction->shouldReceive('execute')
        ->once()
        ->with($user)
        ->andReturn($fakeAccountId);

    $createAccountLinkAction = Mockery::mock(CreateAccountLinkAction::class);
    $createAccountLinkAction->shouldReceive('execute')
        ->once()
        ->with($fakeAccountId)
        ->andReturn($fakeOnboardingUrl);

    $stripeClient = new StripeClient('sk_test');
    $service = new StripeConnectService($stripeClient, $createAccountAction, $createAccountLinkAction);

    $onboardingUrl = $service->startStripeConnectOnboarding($user);

    expect($onboardingUrl)->toEqual($fakeOnboardingUrl);
});
