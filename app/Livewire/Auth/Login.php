<?php

namespace App\Livewire\Auth;

use App\Traits\RealTimeValidation;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    use RealTimeValidation;

    public $email = '';
    public $password = '';
    public $remember = false;

    protected $rules = [
        'email' => ['required', 'email'],
        'password' => ['required'],
    ];

    public function authenticate()
    {
        $this->validate();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('password', 'Incorrect password.');

            return;
        }

        session(['timezone' => get_local_timezone()]);

        return redirect()->intended(route('home'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
