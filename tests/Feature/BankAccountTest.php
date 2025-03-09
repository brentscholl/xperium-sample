<?php

use App\Livewire\Settings\BankAccount;
use App\Models\Host;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects to stripe onboarding when Connect Bank Account is clicked', function () {
    // Create a user and associated host record with a pending status.
    $user = User::factory()->create();
    $host = Host::factory()->create([
        'user_id'              => $user->id,
        'stripe_connect_id'    => 'acct_123',
        'stripe_account_status'=> 'pending',
    ]);

    // Prepare a fake URL returned by the service.
    $fakeOnboardingUrl = 'https://connect.stripe.com/onboarding';

    // Mock the StripeConnectService and bind it into the container.
    $stripeServiceMock = Mockery::mock(StripeConnectService::class);
    $stripeServiceMock->shouldReceive('startStripeConnectOnboarding')
        ->once()
        ->with($user)
        ->andReturn($fakeOnboardingUrl);
    app()->instance(StripeConnectService::class, $stripeServiceMock);

    // Act as the user and call the Livewire method.
    Livewire::actingAs($user)
        ->test(BankAccount::class)
        ->call('startOnboarding')
        ->assertRedirect($fakeOnboardingUrl);
});
