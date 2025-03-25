<?php

    namespace App\Livewire\Settings;

    use App\Services\StripeConnectService;
    use App\Traits\RealTimeValidation;
    use Illuminate\Support\Facades\Auth;
    use Livewire\Component;
    use function App\Http\Livewire\Settings\phone;

    class BankAccount extends Component
    {
        public $loadingStripe = false;

        protected StripeConnectService $stripe_connect_service;

        public function boot(StripeConnectService $stripe_connect_service)
        {
            $this->stripe_connect_service = $stripe_connect_service;
        }

        public function startOnboarding()
        {
            $onboardingUrl = $this->stripe_connect_service->startStripeConnectOnboarding(auth()->user());
            return redirect($onboardingUrl);
        }

        public function updateAccount()
        {
            $loginLink = $this->stripe_connect_service->createAccountLoginLink(auth()->user()->host->stripe_connect_id);
            return redirect()->to($loginLink->url);
        }

        public function render()
        {
            $user = Auth::user();

            return view('livewire.settings.bank-account', compact('user'));
        }
    }
