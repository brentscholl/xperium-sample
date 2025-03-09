<?php

namespace App\Services;

use App\Actions\StripeConnect\CreateAccountLinkAction;
use App\Actions\StripeConnect\CreateStripeConnectAccountAction;
use App\Models\User;
use Stripe\StripeClient;

class StripeConnectService
{
    protected StripeClient $stripe;

    protected CreateStripeConnectAccountAction $createStripeConnectAccountAction;

    protected CreateAccountLinkAction $createConnectAccountLinkAction;

    public function __construct(
        StripeClient $stripe,
        CreateStripeConnectAccountAction $createStripeConnectAccountAction,
        CreateAccountLinkAction $createConnectAccountLinkAction
    ) {
        $this->stripe = $stripe;
        $this->createStripeConnectAccountAction = $createStripeConnectAccountAction;
        $this->createConnectAccountLinkAction = $createConnectAccountLinkAction;
    }

    public function startStripeConnectOnboarding(User $user): string
    {
        $stripeAccountId = $this->createStripeConnectAccountAction->execute($user);

        return $this->createConnectAccountLinkAction->execute($stripeAccountId);
    }

    public function retrieveAccount(string $stripeAccountId)
    {
        return $this->stripe->accounts->retrieve($stripeAccountId);
    }

    public function createAccountLoginLink(string $stripeAccountId)
    {
        return $this->stripe->accounts->createLoginLink($stripeAccountId);
    }
}
