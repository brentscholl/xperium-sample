<?php

use App\Actions\StripeConnect\CreateAccountLinkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

it('creates a new account link', function () {
    $fakeAccountLink = (object)['url' => 'https://stripe.com/onboarding'];

    $stripeClientMock = Mockery::mock(StripeClient::class);
    $accountLinksMock = Mockery::mock();
    $accountLinksMock->shouldReceive('create')
        ->once()
        ->andReturn($fakeAccountLink);
    $stripeClientMock->accountLinks = $accountLinksMock;

    $action = new CreateAccountLinkAction($stripeClientMock);
    $onboardingUrl = $action->execute('acct_test123');

    expect($onboardingUrl)->toEqual('https://stripe.com/onboarding');
});
