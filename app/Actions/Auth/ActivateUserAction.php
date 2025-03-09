<?php

namespace App\Actions\Auth;

use App\Mail\Activate;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ActivateUserAction
{
    public function execute(string $activationCode): ?User
    {
        // Find user by activation code
        $user = User::where('activation_code', $activationCode)->first();

        if (!$user) {
            return null;
        }

        // Activate user
        $user->update([
            'activated' => true,
            'activation_code' => null, // Remove activation code after activation
        ]);

        Auth::login($user);

        return $user;
    }

    public function handleInvalidCode(string $activationCode): ?User
    {
        // Check if a user is logged in
        if (!Auth::check()) {
            return null;
        }

        // Get the authenticated user
        $user = Auth::user();

        // Generate a new activation code
        $newActivationCode = Str::random(30);
        $user->update(['activation_code' => $newActivationCode]);

        // Send the new activation email
        Mail::to($user->email)->queue(new Activate($user));

        return $user;
    }
}
