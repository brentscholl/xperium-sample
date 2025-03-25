<?php

use App\Livewire\Auth\RegisterHost;
use App\Models\Host;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Illuminate\Support\Facades\Mail;
use App\Mail\Welcome;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->validData = [
        'first_name'          => 'Brent',
        'last_name'           => 'Scholl',
        'email'               => 'brent@xperium.com',
        'phone_number'        => '1 306 480 1234',
        'password'            => 'password',
        'password_confirm'    => 'password',
        'date_of_birth_day'   => '19',
        'date_of_birth_month' => '4',
        'date_of_birth_year'  => '1991',
        'terms'               => true,
    ];

    // Simulating what Google Places Autocomplete would return
    $this->googleAddress = [
        'full_address'  => '123 ABC St, North Battleford, SK, Canada',
        'street'        => '123 ABC St',
        'street_2'      => '#123',
        'city'          => 'North Battleford',
        'province'      => 'SK',
        'postal_code'   => 'S9A 1A2',
        'country'       => 'CA',
    ];
});


it('registers a new host', function () {
    Livewire::test(RegisterHost::class)
        ->set($this->validData) // Set regular fields
        ->dispatch('addressSelected', $this->googleAddress) // Simulate Google Places
        ->call('registerHost');

    $user = User::whereEmail('brent@xperium.com')->first();

    expect($user)->not->toBeNull()
        ->and(Host::whereUserId($user->id)->exists())->toBeTrue();
});


it('requires email to be unique', function () {
    User::factory()->create(['email' => 'brent@xperium.com']);

    Livewire::test(RegisterHost::class)
        ->set($this->validData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost')
        ->assertHasErrors(['email' => 'unique']);
});

/**
 * @dataProvider requiredFieldsProvider
 */
it('validates required fields', function ($field) {
    $data = $this->validData;
    $data[$field] = '';

    $test = Livewire::test(RegisterHost::class)
        ->set($data);

    if (!in_array($field, ['street_address_1', 'city', 'province', 'postal_code', 'country'])) {
        // Only dispatch addressSelected if it's not an address-related field
        $test->dispatch('addressSelected', $this->googleAddress);
    }

    $test->call('registerHost')->assertHasErrors([$field => 'required']);
})->with([
    'first_name',
    'last_name',
    'email',
    'phone_number',
    'password',
    'password_confirm',
    'date_of_birth_day',
    'date_of_birth_month',
    'date_of_birth_year',
]);


it('requires terms to be accepted', function () {
    $data = $this->validData;
    $data['terms'] = false;

    Livewire::test(RegisterHost::class)
        ->set($data)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost')
        ->assertHasErrors(['terms' => 'accepted']);
});

it('ensures new hosts are not activated by default', function () {
    Livewire::test(RegisterHost::class)
        ->set($this->validData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost');

    $user = User::whereEmail('brent@xperium.com')->first();

    expect($user->activated)->toBeFalse();
});

it('assigns an activation code to the new host', function () {
    Livewire::test(RegisterHost::class)
        ->set($this->validData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost');

    $user = User::whereEmail('brent@xperium.com')->first();

    expect($user->activation_code)->not->toBeNull();
});

it('grants special outfitter role if valid promo code is used', function () {
    $data = $this->validData;
    $data['promo'] = config('company.promo-code');

    Livewire::test(RegisterHost::class)
        ->set($data)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost');

    $user = User::whereEmail('brent@xperium.com')->first();
    $user->refresh();

    expect($user->hasRole('special_outfitter'))->toBeTrue();
});

it('rejects invalid promo codes', function () {
    $data = $this->validData;
    $data['promo'] = 'invalidcode';

    Livewire::test(RegisterHost::class)
        ->set($data)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost')
        ->assertHasErrors(['promo' => 'in']);
});

it('sends a welcome email to the host', function () {
    Mail::fake(); // Prevent actual emails from being sent

    Livewire::test(RegisterHost::class)
        ->set($this->validData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost');

    $user = User::whereEmail('brent@xperium.com')->first();

    expect($user)->not->toBeNull();

    // Assert that the Welcome email was sent to the correct user
    Mail::assertQueued(Welcome::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});

it('shows a flash message after registration', function () {
    Livewire::test(RegisterHost::class)
        ->set($this->validData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost')
        ->assertSessionHas('flash_notification');
});

it('logs in the user after registration', function () {
    Livewire::test(RegisterHost::class)
        ->set($this->validData)
        ->dispatch('addressSelected', $this->googleAddress)
        ->call('registerHost');

    $user = User::whereEmail('brent@xperium.com')->first();

    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->id)->toBe($user->id);
});



