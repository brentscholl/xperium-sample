<?php

use App\Mail\Activate;
use App\Mail\Welcome;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake(); // Fake emails to prevent actual sending

    $this->user = User::factory()->create([
        'activated' => false,
        'activation_code' => 'test-activation-code',
    ]);

    $this->hostUser = User::factory()->create([
        'activated' => false,
        'activation_code' => 'host-activation-code',
    ]);

    // Attach host relation to the host user
    $this->hostUser->host()->create();
});

it('activates a user with a valid activation code', function () {
    $response = $this->get(route('activate', ['activationCode' => 'test-activation-code']));

    $this->user->refresh();

    $response->assertRedirect('/');
    expect($this->user->activated)->toBeTrue()
        ->and(auth()->check())->toBeTrue();
});


it('does not activate a user with an invalid activation code', function () {
    $response = $this->get(route('activate', ['activationCode' => 'invalid-code']));

    $this->user->refresh();

    $response->assertRedirect('/');
    expect($this->user->activated)->toBeFalse()
        ->and(auth()->check())->toBeFalse();
});

it('shows a flash message after activation', function () {
    $response = $this->get(route('activate', ['activationCode' => 'test-activation-code']));

    $response->assertSessionHas('flash_notification');
});

it('redirects hosts to bank settings after activation', function () {
    $response = $this->get(route('activate', ['activationCode' => 'host-activation-code']));

    $response->assertRedirect(route('settings.bank-account'));
});

it('redirects regular users to the homepage after activation', function () {
    $response = $this->get(route('activate', ['activationCode' => 'test-activation-code']));

    $response->assertRedirect('/');
});


it('handles invalid activation codes and sends a new email only if user is authenticated', function () {
    Mail::fake();

    $user = User::factory()->notActivated()->create();

    expect($user->activation_code)->not->toBeNull();

    $response = $this->get(route('activate', ['activationCode' => 'invalid-code']));

    $response->assertRedirect('/')
        ->assertSessionHas('flash_notification');

    $user->refresh();
    expect($user->activation_code)->not->toBeNull();

    Mail::assertNothingQueued();

    $this->actingAs($user);

    $response = $this->get(route('activate', ['activationCode' => 'invalid-code']));

    $user->refresh();

    $response->assertRedirect('/')
        ->assertSessionHas('flash_notification');

    expect($user->activation_code)->not->toBeNull()
        ->and($user->activation_code)->not->toBe('invalid-code');

    Mail::assertQueued(Activate::class, function ($mail) use ($user) {
        return $mail->hasTo($user->email);
    });
});





