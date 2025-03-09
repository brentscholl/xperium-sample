<?php

namespace App\Actions\Auth;

use App\Mail\Welcome;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegisterUserAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name'      => Str::ucfirst($data['first_name']),
                'last_name'       => Str::ucfirst($data['last_name']),
                'email'           => $data['email'],
                'password'        => isset($data['password']) ? Hash::make($data['password']) : null,
                'activation_code' => $data['provider'] ?? null ? null : Str::random(30),
                'provider_id'     => $data['provider_id'] ?? null,
                'provider'        => $data['provider'] ?? null,
                'activated'       => $data['activated'] ?? false,
                'phone_number'    => $data['phone_number'] ?? null,
            ]);

            if (empty($data['provider'])) {
                Mail::to($user)->queue(new Welcome($user));
            }

            auth()->login($user);

            return $user;
        });
    }
}
