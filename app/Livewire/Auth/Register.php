<?php

namespace App\Livewire\Auth;

use App\Services\UserService;
use App\Traits\RealTimeValidation;
use Livewire\Component;

class Register extends Component
{
    use RealTimeValidation;

    public $first_name = '';
    public $last_name = '';
    public $email = '';
    public $password = '';
    public $password_confirm = '';
    public $terms = false;

    protected $userService;

    public function boot(UserService $userService)
    {
        $this->userService = $userService;
    }

    protected function rules()
    {
        return [
            'first_name'       => ['required', 'string', 'min:2', 'max:12'],
            'last_name'        => ['required', 'string', 'min:2', 'max:12'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'string', 'min:6'],
            'password_confirm' => ['required', 'string', 'same:password'],
            'terms'            => ['accepted'],
        ];
    }

    public function register()
    {
        $this->validate();

        // Use the service to register and log in the user
        $user = $this->userService->registerUser([
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'email'      => $this->email,
            'password'   => $this->password,
        ]);

        flash()->overlay(
            'We’ve sent you an email with instructions to activate your account. After activating your account, you’ll be able to book experiences.',
            'Please check your email'
        );

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}

