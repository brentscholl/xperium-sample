<?php
use App\Services\UserService;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use Illuminate\Support\Facades\Mail;
use App\Mail\Welcome;
use Illuminate\Support\Facades\Session;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function () {
    $this->userData = [
        'first_name'       => 'Brent',
        'last_name'        => 'Scholl',
        'email'            => 'brent@xperium.com',
        'password'         => 'password',
        'password_confirm' => 'password',
        'terms'            => true,
    ];
});

// Helper function to register a user
function registerUser($overrides = [])
{
    return Livewire::test(Register::class)
        ->set(array_merge(test()->userData, $overrides))
        ->call('register');
}

it('allows a user to see the registration page', function () {
    $this->get('/register')->assertStatus(200);
});

it('allows a user to register', function () {
    $userService = Mockery::mock(UserService::class);
    $fakeUser = User::factory()->make(['email' => $this->userData['email']]);

    $userService->shouldReceive('registerUser')->once()->andReturn($fakeUser);
    $this->app->instance(UserService::class, $userService);

    registerUser();

    $userService->shouldHaveReceived('registerUser')->once();
});

it('redirects user to home after registration', function () {
    $userService = Mockery::mock(UserService::class);
    $fakeUser = User::factory()->create();
    $userService->shouldReceive('registerUser')->once()->andReturn($fakeUser);
    $this->app->instance(UserService::class, $userService);

    registerUser()->assertRedirect('/');
});

it('logs in the user after registration', function () {
    registerUser();

    expect(auth()->check())->toBeTrue();
    expect(auth()->user())->not->toBeNull();
    expect(auth()->user()->email)->toBe($this->userData['email']);
});

it('calls the user service and redirects', function () {
    $userService = Mockery::mock(UserService::class);
    $fakeUser = User::factory()->create();
    $userService->shouldReceive('registerUser')->once()->andReturn($fakeUser);
    $this->app->instance(UserService::class, $userService);

    registerUser()->assertRedirect('/');
});

it('sends a welcome email after registration', function () {
    Mail::fake();

    registerUser();

    Mail::assertQueued(Welcome::class, fn ($mail) => $mail->hasTo($this->userData['email']));
});

it('shows a flash message after registration', function () {
    registerUser();

    expect(session()->get('flash_notification'))->not->toBeNull();
    expect(session()->get('flash_notification.0.message'))
        ->toContain('We’ve sent you an email with instructions to activate your account');
});

/**
 * @dataProvider requiredFieldsProvider
 */
it('requires fields to be filled', function ($field) {
    registerUser([$field => ''])->assertHasErrors([$field => 'required']);
})->with([
    'first_name',
    'last_name',
    'email',
    'password',
    'password_confirm',
]);

it('requires email to be unique', function () {
    User::factory()->create(['email' => $this->userData['email']]);

    registerUser()->assertHasErrors(['email' => 'unique']);
});
