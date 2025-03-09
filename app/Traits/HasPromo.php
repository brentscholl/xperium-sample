<?php

    namespace App\Traits;

    use Illuminate\Support\Str;

    trait HasPromo
    {
        public function updatingPromo()
        {
            $this->resetErrorBag('promo');
            $this->promo_is_valid = false;
        }

        public function updatedPromo()
        {
            if (config('company.use-promo-code')) {
                $this->promo_is_valid = Str::lower($this->promo) === config('company.promo-code');
            }
        }

    }
