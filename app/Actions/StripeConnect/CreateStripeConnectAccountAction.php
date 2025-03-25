<?php

namespace App\Actions\StripeConnect;

use App\Models\User;
use Stripe\StripeClient;

class CreateStripeConnectAccountAction
{
    protected StripeClient $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }

    public function execute(User $user): string
    {
        if ($user->host->stripe_account_id) {
            return $user->host->stripe_account_id;
        }

        $connectAccount = $this->stripe->accounts->create([
            'country' => 'CA',
            'email' => $user->email,
            'business_profile' => [
                'url' => config('app.url'),
                'product_description' => 'Experiences',
            ],
            'controller' => [
                'fees' => ['payer' => 'application'],
                'losses' => ['payments' => 'application'],
                'stripe_dashboard' => ['type' => 'express'],
            ],
            'business_type' => 'individual',
            'individual' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'address' => [
                    'city' => $user->host->city,
                    'country' => 'CA',
                    'line1' => $user->host->street_address_1,
                    'line2' => $user->host->street_address_2,
                    'postal_code' => $user->host->postal_code,
                    'state' => $user->host->province,
                ],
                'dob' => [
                    'day' => $user->host->date_of_birth->format('d'),
                    'month' => $user->host->date_of_birth->format('m'),
                    'year' => $user->host->date_of_birth->format('Y'),
                ],
            ],
        ]);

        $user->host->update(['stripe_connect_id' => $connectAccount->id]);

        return $connectAccount->id;
    }
}
