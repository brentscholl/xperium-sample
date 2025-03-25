<?php

namespace App\Livewire\Settings;

use App\Services\HostService;
use App\Traits\HasAddress;
use App\Traits\HasPromo;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class BecomeHost extends Component
{
    use HasPromo;
    use HasAddress;

    public $phone_number, $date_of_birth_day, $date_of_birth_month, $date_of_birth_year;

    public $url, $bio;

    public $promo = '', $promo_is_valid = false, $terms = false;

    protected HostService $host_service;

    public function boot(HostService $host_service)
    {
        $this->host_service = $host_service;
    }

    public function rules()
    {
        $rules = [
            'date_of_birth_day'   => ['required'],
            'date_of_birth_month' => ['required'],
            'date_of_birth_year'  => ['required'],
            'address'             => ['required', 'string'],
            'promo'               => ['nullable', 'string', 'in:'.config('company.promo-code')],
            'terms'               => ['accepted'],
        ];

        // Only require phone number if the user doesn't already have one
        if (empty(auth()->user()->phone_number)) {
            $rules['phone_number'] = ['required', 'min:10', 'max:15', 'phone:CA'];
        }
        return $rules;
    }

    public function upgradeToHost()
    {
        if (auth()->user()->isHost()) {
            flash()->error('You are already a Host. Visit your bank detail settings to update your account.');
            return false;
        }

        if ($this->country !== 'CA') {
            $this->addError('address', 'Sorry, only Canadian users can become hosts at this time.');
            return false;
        }

        $this->validate();

        try {
            // Build host data for individual and company accounts
            $host_data = [
                'user_id'             => auth()->user()->id,
                'email'               => auth()->user()->email,
                'first_name'          => auth()->user()->first_name,
                'last_name'           => auth()->user()->last_name,
                'phone_number'        => $this->phone_number ?: auth()->user()->phone_number,
                'date_of_birth_day'   => $this->date_of_birth_day,
                'date_of_birth_month' => $this->date_of_birth_month,
                'date_of_birth_year'  => $this->date_of_birth_year,
                'address'             => $this->address,
                'country'             => 'CA',
                'street'              => $this->street_address_1,
                'street_2'            => $this->street_address_2,
                'city'                => $this->city,
                'province'            => $this->province,
                'postal_code'         => $this->postal_code,
                'promo'               => $this->promo,
            ];

            $this->host_service->upgradeUserToHost(auth()->user(), $host_data);

            flash()->overlay('You can now create FREE Experiences. If you would like to get paid for doing what you love, you will need to connect your bank account next.', 'You are now a Host.');

            return redirect()->route('settings.bank-account');
        } catch (\Exception $e) {
            Log::error("Error upgrading user to Host. User id".auth()->user()->id." : {$e->getMessage()}");
            flash()->error('There was a problem upgrading your account. Please try again later.');

            return false;
        }
    }

    public function render()
    {
        return view('livewire.settings.become-host');
    }
}
