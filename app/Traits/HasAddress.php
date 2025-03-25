<?php

namespace App\Traits;

use Livewire\Attributes\On;
use Livewire\Features\SupportEvents\Event;

trait HasAddress
{
    public $country, $street_address_1, $street_address_2, $city, $province, $postal_code;
    public $address;

    #[On('addressSelected')]
    public function addressSelected($data)
    {
        if (!is_array($data)) {
            $this->addError('address', 'Invalid address data provided.');
            \Log::error('Invalid data received for addressSelected:', [$data]);
            return;
        }

        $this->address = $data['full_address'] ?? '';
        $this->street_address_1 = $data['street'] ?? '';
        $this->street_address_2 = $data['street_2'] ?? '';
        $this->city = $data['city'] ?? '';
        $this->province = $data['province'] ?? '';
        $this->postal_code = $data['postal_code'] ?? '';
        $this->country = $data['country'] ?? 'CA';
    }
}
