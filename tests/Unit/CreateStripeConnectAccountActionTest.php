<?php

use App\Actions\StripeConnect\CreateStripeConnectAccountAction;
use App\Models\User;
use App\Models\Host;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;

uses(RefreshDatabase::class);

it('creates a new stripe connect account if not exists', function () {
    $user = User::factory()->create([
        'first_name'   => 'John',
        'last_name'    => 'Doe',
        'email'        => 'john@example.com',
        'phone_number' => '1234567890',
    ]);
    // Create a host record without a stripe_connect_id.
    $host = Host::factory()->create(['user_id' => $user->id, 'stripe_connect_id' => null]);

    $fakeStripeAccount = (object)['id' => 'acct_test123'];

    $stripeClientMock = Mockery::mock(StripeClient::class);
    $accountsMock = Mockery::mock();
    $accountsMock->shouldReceive('create')
        ->once()
        ->andReturn($fakeStripeAccount);
    $stripeClientMock->accounts = $accountsMock;

    $action = new CreateStripeConnectAccountAction($stripeClientMock);

    $accountId = $action->execute($user);

    expect($accountId)->toEqual('acct_test123');

    $host->refresh();
    expect($host->stripe_connect_id)->toEqual('acct_test123');
});
