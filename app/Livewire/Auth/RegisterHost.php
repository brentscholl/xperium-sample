<?php

namespace App\Livewire\Auth;

use App\Services\HostService;
use App\Traits\HasAddress;
use App\Traits\HasPromo;
use Livewire\Component;
use Propaganistas\LaravelPhone\PhoneNumber;

class RegisterHost extends Component
{
    use HasPromo;
    use HasAddress;

    public $first_name, $last_name, $email, $phone_number, $password, $password_confirm;

    public $date_of_birth_day, $date_of_birth_month, $date_of_birth_year;

    public $promo = '', $promo_is_valid = false, $terms = false;

    protected $hostService;

    public function boot(HostService $hostService)
    {
        $this->hostService = $hostService;
    }

    public function rules()
    {
        return [
            'first_name'          => ['required', 'string', 'min:2', 'max:12'],
            'last_name'           => ['required', 'string', 'min:2', 'max:12'],
            'email'               => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number'        => ['required', 'min:10', 'max:15', 'phone:CA,US'],
            'password'            => ['required', 'string', 'min:6'],
            'password_confirm'    => ['required', 'same:password'],
            'date_of_birth_day'   => ['required'],
            'date_of_birth_month' => ['required'],
            'date_of_birth_year'  => ['required'],
            'address'             => ['required', 'string'],
            'promo'               => ['nullable', 'string', 'in:'.config('company.promo-code')],
            'terms'               => ['accepted'],
        ];
    }

    public function registerHost()
    {
        if ($this->country !== 'CA') {
            flash()->error('Only users in Canada can become hosts at this time.');
            return redirect()->back();
        }

        $this->validate();

        $this->hostService->registerHost([
            'first_name'          => $this->first_name,
            'last_name'           => $this->last_name,
            'email'               => $this->email,
            'phone_number'        => $this->phone_number,
            'password'            => $this->password,
            'date_of_birth_day'   => $this->date_of_birth_day,
            'date_of_birth_month' => $this->date_of_birth_month,
            'date_of_birth_year'  => $this->date_of_birth_year,
            'country'             => $this->country,
            'street'              => $this->street_address_1,
            'street_2'            => $this->street_address_2,
            'city'                => $this->city,
            'province'            => $this->province,
            'postal_code'         => $this->postal_code,
            'promo'               => $this->promo,
        ]);

        flash()->overlay('We’ve sent you an email with instructions to activate your account. After activating your account, you’ll be able to book and host experiences.',
            'You’re almost done!');

        return redirect('/');
    }

    public function render()
    {
        return view('livewire.auth.register-host');
    }
}
