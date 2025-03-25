<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class HandleSocialLoginAction
{
    /**
     * Attempt to log in an existing user.
     */
    public function execute($socialUser)
    {
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            Auth::login($user);
            session(['timezone' => get_local_timezone()]);
            flash()->success('You have been logged in');
            return $user;
        }

        return null;
    }
}
