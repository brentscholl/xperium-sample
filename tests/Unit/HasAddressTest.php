<?php

use App\Traits\HasAddress;
use Livewire\Component;
use Livewire\Livewire;
use Illuminate\Support\Facades\Log;

class DummyAddressComponent extends Component
{
    use HasAddress;

    public function render()
    {
        return <<<'blade'
        <div></div>
        blade;
    }
}

it('sets address properties when valid data is provided', function () {
    $addressData = [
        'full_address' => '456 Test Ave, Test City, TS, Canada',
        'street'       => '456 Test Ave',
        'street_2'     => 'Suite 100',
        'city'         => 'Test City',
        'province'     => 'TS',
        'postal_code'  => 'T3S 2P1',
        'country'      => 'CA',
    ];

    Livewire::test(DummyAddressComponent::class)
        ->call('addressSelected', $addressData)
        ->assertSet('address', '456 Test Ave, Test City, TS, Canada')
        ->assertSet('street_address_1', '456 Test Ave')
        ->assertSet('street_address_2', 'Suite 100')
        ->assertSet('city', 'Test City')
        ->assertSet('province', 'TS')
        ->assertSet('postal_code', 'T3S 2P1')
        ->assertSet('country', 'CA');
});

it('logs an error when invalid address data is provided', function () {
    Log::spy();

    Livewire::test(DummyAddressComponent::class)
        ->call('addressSelected', 'invalid data')
        ->assertHasErrors('address');

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn($message, $context) => str_contains($message, 'Invalid data received for addressSelected'));
});
