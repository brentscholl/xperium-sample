<?php

namespace App\Actions\Host;

use App\Models\Host;
use App\Models\User;

class RegisterHostAction
{
    public function execute(User $user, array $data)
    {
        if ($data['country'] !== 'CA') {
            throw new \RuntimeException("Only Canadian users can become hosts.");
        }

        return Host::create([
            'user_id'          => $user->id,
            'date_of_birth'    => "{$data['date_of_birth_year']}-{$data['date_of_birth_month']}-{$data['date_of_birth_day']}",
            'country'          => 'CA',
            'street_address_1' => $data['street'],
            'street_address_2' => $data['street_2'],
            'city'             => $data['city'],
            'province'         => $data['province'],
            'postal_code'      => $data['postal_code'],
        ]);
    }
}
