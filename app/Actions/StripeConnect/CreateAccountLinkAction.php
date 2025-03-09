<?php

namespace App\Actions\StripeConnect;

use Stripe\StripeClient;

class CreateAccountLinkAction
{
    protected StripeClient $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = $stripe;
    }

    public function execute(string $stripe_connect_id): string
    {
        $accountLink = $this->stripe->accountLinks->create([
            'account' => $stripe_connect_id,
            'refresh_url' => route('stripe.onboarding.refresh'),
            'return_url' => route('stripe.onboarding.return'),
            'type' => 'account_onboarding',
            'collection_options' => ['fields' => 'eventually_due'],
        ]);

        return $accountLink->url;
    }
}
