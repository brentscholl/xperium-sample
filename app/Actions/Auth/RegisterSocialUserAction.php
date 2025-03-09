<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterSocialUserAction
{
    protected RegisterUserAction $registerUserAction;

    public function __construct(RegisterUserAction $registerUserAction)
    {
        $this->registerUserAction = $registerUserAction;
    }

    /**
     * Register a new user via OAuth and log them in.
     */
    public function execute($socialUser, string $provider)
    {
        $nameParts = explode(' ', trim($socialUser->getName()));
        $firstName = array_shift($nameParts);
        $lastName = implode(' ', $nameParts);

        $userData = [
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'email'       => $socialUser->getEmail(),
            'password'    => null, // Social users don’t have a password initially
            'provider_id' => $socialUser->getId(),
            'provider'    => $provider,
            'activated'   => true,
        ];

        $user = $this->registerUserAction->execute($userData);

        Auth::login($user);
        session(['timezone' => get_local_timezone()]);
        flash()->overlay('Thanks for joining in on the fun! You are now logged in.', 'Welcome to Xperium!');

        return $user;
    }
}
