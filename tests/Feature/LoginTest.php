<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Mail\Activate;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('shows the login form', function () {
    $response = $this->get(route('login'));
    $response->assertStatus(200);
    $response->assertViewIs('auth.login');
});

it('validates the login request', function () {
    $response = $this->post(route('login'), []);
    $response->assertSessionHasErrors(['email', 'password']);
});

it('rejects invalid credentials', function () {
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertFalse(Auth::check());
});

it('logs in successfully and redirects', function () {
    $response = $this->post(route('login'), [
        'email' => $this->user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/');
    $this->assertAuthenticatedAs($this->user);
});

it('throttles after too many failed login attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

it('logs out successfully', function () {
    Auth::login($this->user);

    $response = $this->post(route('logout'));

    $response->assertRedirect('/');
    $this->assertGuest();
});

it('logs in successfully and sends an activation email if user is not activated', function () {
    Mail::fake();

    $inactiveUser = User::factory()->notActivated()->create();

    $response = $this->post(route('login'), [
        'email' => $inactiveUser->email,
        'password' => 'password',
    ]);

    // Ensure user is authenticated
    $this->assertAuthenticatedAs($inactiveUser);

    // Check that an activation email was sent
    Mail::assertQueued(Activate::class, function ($mail) use ($inactiveUser) {
        return $mail->hasTo($inactiveUser->email);
    });

    // Ensure flash message is set
    $response->assertSessionHas('flash_notification');
});


