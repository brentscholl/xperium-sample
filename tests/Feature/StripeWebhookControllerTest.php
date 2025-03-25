<?php

use App\Http\Controllers\StripeWebhookController;
use App\Mail\StripeAccountUpdateRequiredMail;
use App\Models\Host;
use App\Models\User;
use App\Notifications\UpdateBankAccount;
use App\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;

uses(RefreshDatabase::class);

it('handles account.updated event, marks account incomplete, and notifies the user', function () {
    Mail::fake();
    Notification::fake();

    $user = User::factory()->create(['email' => 'test@example.com']);
    $host = Host::factory()->create([
        'user_id'               => $user->id,
        'stripe_connect_id'     => 'acct_test123',
        'stripe_account_status' => 'completed',
    ]);

    $fakeEvent = (object)[
        'id'   => 'evt_test',
        'type' => 'account.updated',
        'data' => (object)[
            'object' => (object)[
                'id' => 'acct_test123',
                'requirements' => (object)[
                    'current_deadline' => '2025-01-01',
                ],
            ],
        ],
    ];

    $payload = json_encode(['dummy' => 'payload']);
    $request = Request::create('/stripe/webhook', 'POST', [], [], [], [], $payload);
    $request->headers->set('Stripe-Signature', 'dummy_signature');

    $dummyWebhookService = Mockery::mock(StripeWebhookService::class);
    $dummyWebhookService->shouldReceive('processWebhook')
        ->once()
        ->with($request)
        ->andReturn($fakeEvent);

    app()->instance(StripeWebhookService::class, $dummyWebhookService);

    $controller = app()->make(StripeWebhookController::class);

    $controller->handleWebhook($request);

    $host->refresh();
    expect($host->stripe_account_status)->toEqual('incomplete');

    Mail::assertQueued(StripeAccountUpdateRequiredMail::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });

    Notification::assertSentTo($user, UpdateBankAccount::class);
});

