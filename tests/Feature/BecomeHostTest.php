<?php

use App\Livewire\Settings\BecomeHost;
use App\Models\Host;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    Log::spy();

    // Create a user who already has a phone number.
    $this->user = User::factory()->create([
        'first_name'   => 'Brent',
        'last_name'    => 'Scholl',
        'email'        => 'brent@xperium.com',
        'phone_number' => '13064801234',
    ]);

    // Example address data (from the address event).
    $this->googleAddress = [
        'full_address' => '123 ABC St, North Battleford, SK, Canada',
        'street'       => '123 ABC St',
        'street_2'     => '#123',
        'city'         => 'North Battleford',
        'province'     => 'SK',
        'postal_code'  => 'S9A 1A2',
        'country'      => 'CA',
    ];

    // Valid host data for the component.
    $this->validHostData = [
        'phone_number'        => '13064801234',
        'date_of_birth_day'   => '19',
        'date_of_birth_month' => '04',
        'date_of_birth_year'  => '1991',
        'promo'               => '',
        'terms'               => true,
        // Note: The address fields (address, street_address_1, etc.) are set via the addressSelected event.
    ];
});

it('allows a user to become a host', function () {
    Livewire::actingAs($this->user)
        ->test(BecomeHost::class)
        ->set($this->validHostData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('upgradeToHost');

    $host = Host::where('user_id', $this->user->id)->first();
    expect($host)->not->toBeNull();
});

it('requires terms to be accepted', function () {
    // Set terms to false. The component should return early with a redirect.
    $this->validHostData['terms'] = false;

    $component = Livewire::actingAs($this->user)
        ->test(BecomeHost::class)
        ->set($this->validHostData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('upgradeToHost');

    $component->assertHasErrors(['terms' => 'accepted']);
});

it('validates required fields', function ($field) {
    $data = $this->validHostData;
    $data[$field] = ''; // Set the field empty to trigger a validation error.

    Livewire::actingAs($this->user)
        ->test(BecomeHost::class)
        ->dispatch('addressSelected', $this->googleAddress)
        ->set($data)
        ->call('upgradeToHost')
        ->assertHasErrors([$field]);

})->with([
    'date_of_birth_day',
    'date_of_birth_month',
    'date_of_birth_year',
    'address',
]);

it('does not require phone number if user already has one', function () {
    // Remove phone_number from form data. Since the user already has one, no validation error should occur.
    unset($this->validHostData['phone_number']);

    Livewire::actingAs($this->user)
        ->test(BecomeHost::class)
        ->set($this->validHostData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('upgradeToHost')
        ->assertHasNoErrors(['phone_number']);
});

it('redirects the user after successful upgrade', function () {
    $component = Livewire::actingAs($this->user)
        ->test(BecomeHost::class)
        ->set($this->validHostData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('upgradeToHost');

    // Expect the redirect to match the component's actual redirect URL.
    $component->assertRedirect(route('settings.bank-account'));
});

it('prevents upgrade if the user is already a host', function () {
    // Create an existing host record for the user.
    Host::factory()->create([
        'user_id'      => $this->user->id,
        'account_type' => 'individual',
    ]);

    $this->user->refresh();

    $component = Livewire::actingAs($this->user)
        ->test(BecomeHost::class)
        ->set($this->validHostData)
        ->dispatch('addressSelected', $this->googleAddress);

    // Call the method and capture the TestResponse.
    $response = $component->call('upgradeToHost');

    $response->assertReturned(false);
});

it('handles invalid address event gracefully', function () {
    // Dispatch invalid address data (not an array).
    $component = Livewire::actingAs($this->user)
        ->test(BecomeHost::class)
        ->call('addressSelected', 'invalid data');

    // Expect the address property remains empty.
    $component->assertSet('address', '');

    // Assert that an error was logged (using Log::spy() earlier).
    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn($message, $context) => str_contains($message, 'Invalid data received for addressSelected'));
});

it('requires phone number if user does not have one', function () {
    $userWithoutPhone = User::factory()->create([
        'first_name'   => 'NoPhone',
        'last_name'    => 'User',
        'email'        => 'nophone@example.com',
        'phone_number' => null,
    ]);
    $data = $this->validHostData;
    unset($data['phone_number']);

    Livewire::actingAs($userWithoutPhone)
        ->test(BecomeHost::class)
        ->set($data)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('upgradeToHost')
        ->assertHasErrors(['phone_number']);
});
