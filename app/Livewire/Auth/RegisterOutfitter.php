<?php

    namespace App\Livewire\Auth;

    use App\Mail\Welcome;
    use App\Models\Host;
    use App\Models\User;
    use App\Traits\HasPromo;
    use App\Traits\RealTimeValidation;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Str;
    use Livewire\Component;
    use Propaganistas\LaravelPhone\PhoneNumber;
    use Stripe\Stripe;

    class RegisterOutfitter extends Component
    {
        use RealTimeValidation, HasPromo;

        public $first_name = '';

        public $last_name = '';

        public $email = '';

        public $phone_number = '';

        public $password = '';

        public $password_confirm = '';

        public $account_type = '';

        public $date_of_birth_day = '';

        public $date_of_birth_month = '';

        public $date_of_birth_year = '';

        public $country = '';

        public $street_address_1 = '';

        public $street_address_2 = '';

        public $city = '';

        public $province = '';

        public $postal_code = '';

        public $company = '';

        public $url = '';

        public $transit_number = '';

        public $institution_number = '';

        public $account_number = '';

        public $account_number_confirmation = '';

        public $promo = '';

        public $promo_is_valid = false;

        public $terms = false;

        public $stripe_token = null;
        protected $listeners = ['setStripeToken'];

        /**
         * todo: add unique validation to phone
         * @return \string[][]
         */
        public function rules()
        {
            return [
                'first_name'       => ['required', 'string', 'min:2', 'max:12'],
                'last_name'        => ['required', 'string', 'min:2', 'max:12'],
                'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'phone_number'     => ['required', 'min:10', 'max:15', 'phone:AUTO,CA,US',],
                'password'         => ['required', 'string', 'min:6'],
                'password_confirm' => ['required', 'string', 'same:password'],

                'company' => ['string', 'nullable'],
                'url'     => ['string', 'nullable'],

                'account_type'                => ['required'],
                'date_of_birth_day'           => ['required'],
                'date_of_birth_month'         => ['required'],
                'date_of_birth_year'          => ['required'],
                'country'                     => ['required', 'in:CA'],
                'street_address_1'            => ['required', 'string'],
                'street_address_2'            => ['nullable', 'string'],
                'city'                        => ['required', 'string'],
                'province'                    => ['required', 'string'],
                'postal_code'                 => ['required', 'string'],
                'transit_number'              => ['required', 'digits:5'],
                'institution_number'          => ['required', 'digits:3'],
                'account_number'              => ['required', 'numeric'],
                'account_number_confirmation' => ['required', 'numeric', 'same:account_number'],

                'promo' => ['string', 'nullable', 'in:'.config('company.promo-code')],

                'terms' => ['accepted'],
            ];
        }

        protected $messages = [
            'country.in' => 'Sorry. Only users in Canada can currently be an Host on Xperium. Please check back for when we will be available in the selected country',
            'promo.in' => 'Your code is invalid.'
        ];

        public function validateOutfitter() {
            $this->validate();

            // Fire event that Alpine will listen to and attempt
            // to send card data to Stripe. If successful will set
            // $stripe_token in Livewire component
            $this->dispatch('generate-stripe-token');
        }

        /**
         * todo: add individual.id_number to connect account https://stripe.com/docs/api/accounts/create
         *
         * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
         */
        public function submitForm()
        {

            DB::beginTransaction();
            // Create the user =========================================
            $activationCode = Str::random(30);
            // Set phone number
            $currentUserLocation = currentUserLocation();
            $phoneNumber = PhoneNumber::make($this->phone_number, $currentUserLocation->iso_code)->formatInternational();

            $user = User::create([
                'first_name'      => $this->first_name,
                'last_name'       => $this->last_name,
                'email'           => $this->email,
                'phone_number'    => $phoneNumber,
                'password'        => Hash::make($this->password),
                'activation_code' => $activationCode,
            ]);

            $user->settings()->apply();

            // Create the host =========================================
            $dob = date('Y-m-d', strtotime($this->date_of_birth_day.'-'.$this->date_of_birth_month.'-'.$this->date_of_birth_year));
            $host = Host::create([
                'user_id'             => $user->id,
                'account_type'        => $this->account_type,
                'date_of_birth_day'   => $this->date_of_birth_day,
                'date_of_birth_month' => $this->date_of_birth_month,
                'date_of_birth_year'  => $this->date_of_birth_year,
                'country'             => $this->country,
                'street_address_1'    => $this->street_address_1,
                'street_address_2'    => $this->street_address_2,
                'city'                => $this->city,
                'province'            => $this->province,
                'postal_code'         => $this->postal_code,
                'company'             => $this->company,
                'url'                 => $this->url,
                'transit_number'      => $this->transit_number,
                'institution_number'  => $this->institution_number,
                'account_number'      => $this->account_number,
            ]);

            // Set up Stripe Connect Account =================================
            $stripe = new \Stripe\StripeClient(config('stripe.stripe_secret'));
            $connectParams = [
                'type'             => 'custom',
                'country'          => $this->country,
                'email'            => $this->email,
                'capabilities'     => [
                    'card_payments' => ['requested' => true],
                    'transfers'     => ['requested' => true],
                ],
                'business_type'    => $this->account_type,
                'tos_acceptance'   => [
                    'date'       => now()->timestamp,
                    'ip'         => request()->ip(),
                    'user_agent' => request()->server('HTTP_USER_AGENT'),
                ],
                'default_currency' => 'cad',
                'external_account' => [
                    'object'              => 'bank_account',
                    'country'             => $this->country,
                    'currency'            => 'cad',
                    'account_holder_name' => $this->first_name.' '.$this->last_name,
                    'account_holder_type' => $this->account_type,
                    'routing_number'      => $this->transit_number.$this->institution_number,
                    'account_number'      => $this->account_number,
                ],
                'settings'         => [
                    'payouts' => [
                        'debit_negative_balances' => true,
                        'schedule'                => [
                            'interval' => 'manual',
                        ],
                    ],
                ],
            ];
            if ($this->account_type == 'individual') {
                array_push($connectParams, [
                    'individual' => [
                        'address'    => [
                            'city'        => $this->city,
                            'country'     => $this->country,
                            'line1'       => $this->street_address_1,
                            'line2'       => $this->street_address_2,
                            'postal_code' => $this->postal_code,
                            'state'       => $this->province,
                        ],
                        'dob'        => [
                            'day'   => $this->date_of_birth_day,
                            'month' => $this->date_of_birth_month,
                            'year'  => $this->date_of_birth_year,
                        ],
                        'email'      => $this->email,
                        'first_name' => $this->first_name,
                        'last_name'  => $this->last_name,
                        'phone'      => $this->phone_number,

                    ],
                ]);
            }
            if ($this->account_type == 'company') {
                array_push($connectParams, [
                    'company' => [
                        'address' => [
                            'city'        => $this->city,
                            'country'     => $this->country,
                            'line1'       => $this->street_address_1,
                            'line2'       => $this->street_address_2,
                            'postal_code' => $this->postal_code,
                            'state'       => $this->province,
                        ],
                        'name'    => $this->email,
                        'phone'   => $this->phone_number,
                    ],
                ]);
            }

            $connect = $stripe->accounts->create($connectParams);

            // Link Stripe Connect to Host
            $host->update([
                'stripe_connect_id' => $connect->id,
            ]);

            // Create the Subscription =================================================================================

            // Apply Promo code (if applicable) =========================================
            Stripe::setApiKey(config('stripe.stripe_secret'));
            if (config('company.use-promo-code')) {
                if (Str::lower($this->promo) === config('company.promo-code')) {
                    $user->special_outfitter = true;
                    $user->roles()->attach('1');
                    $user->save();
                } else {
                    $user->newSubscription('outfitter', config('stripe.stripe_id_monthly_subscription'))->create($this->stripe_token);
                }
            } else {
                $user->newSubscription('outfitter', config('stripe.stripe_id_monthly_subscription'))->create($this->stripe_token);
            }

            DB::commit();

            // Email the new user Welcome message with their activation code
            \Mail::to($user)->queue(new Welcome($user));

            flash()->overlay('An email has been sent to you with instructions to <strong>activate your account</strong>.
            You will need to complete this before you can host or book experiences', 'You are almost done!');

            // Redirect to the home page
            return redirect('/');
        }

        public function setStripeToken($stripeToken)
        {
            $this->stripe_token = $stripeToken;
        }

        public function render()
        {
            Stripe::setApiKey(config('stripe.stripe_secret'));
            $user = new User;

            return view('livewire.auth.register-outfitter', [
                'intent' => $user->createSetupIntent(),
            ]);
        }
    }
