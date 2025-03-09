<?php

namespace App\Services;

use App\Actions\Auth\ActivateUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\Mail\Activate;
use App\Models\Experience;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    protected RegisterUserAction $registerUserAction;
    protected ActivateUserAction $activateUserAction;

    public function __construct(RegisterUserAction $registerUserAction, ActivateUserAction $activateUserAction)
    {
        $this->registerUserAction = $registerUserAction;
        $this->activateUserAction = $activateUserAction;
    }

    /**
     * Register a new user and log them in.
     *
     * @param array $data
     * @return User
     */
    public function registerUser(array $data): User
    {
        return $this->registerUserAction->execute($data);
    }

    /**
     * Activate the user by updating the activated status.
     *
     * @param string $activationCode
     * @return User|null
     */
    public function activateUser(string $activationCode): ?User
    {
        return $this->activateUserAction->execute($activationCode);
    }

    public function handleInvalidActivationCode(User $user): void
    {
        // Generate a new activation code
        $newActivationCode = Str::random(30);
        $user->update(['activation_code' => $newActivationCode]);

        // Send activation email
        Mail::to($user->email)->queue(new Activate($user));
    }

    /**
     * Check if the authenticated user is activated.
     *
     * @return bool
     */
    public function checkIfActivated(): bool
    {
        $user = Auth::user();

        if ($user && !$user->activated) {
            Mail::to($user)->queue(new Activate($user));
            return false;
        }

        return true;
    }

    /**
     * Check if the user has reached their experience creation limit.
     *
     * @return bool
     */
    public function checkExperienceCreationLimit(): bool
    {
        $user = Auth::user();

        if ($user && !$user->hasRole('outfitter')) {
            $experienceCount = Experience::where('user_id', $user->id)->count();
            return $experienceCount < config('company.max-experiences');
        }

        return true;
    }
}
