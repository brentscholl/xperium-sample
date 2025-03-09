<?php

namespace App\Services;

use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookService
{
    protected string $webhookSecret;

    public function __construct()
    {
        $this->webhookSecret = config('services.stripe.webhook_secret');
    }

    /**
     * Process and verify the incoming webhook.
     */
    public function processWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        // In testing, bypass signature verification.
        if (app()->environment('testing')) {
            return json_decode($payload);
        }

        try {
            return Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);
        } catch (\UnexpectedValueException $e) {
            abort(400, 'Invalid payload');
        } catch (SignatureVerificationException $e) {
            abort(400, 'Invalid signature');
        }
    }
}
