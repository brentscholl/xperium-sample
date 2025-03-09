<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;
use App\Services\StripeWebhookService;

class StripeServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Existing binding for StripeClient
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('services.stripe.secret'));
        });

        // Register the webhook service
        $this->app->singleton(StripeWebhookService::class, function () {
            return new StripeWebhookService();
        });
    }

    public function boot()
    {
        //
    }
}
