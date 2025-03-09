<?php

namespace App\Actions\Host;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

class ApplyPromoAction
{
    public function execute(User $user, ?string $promo)
    {
        if (config('company.use-promo-code') && Str::lower($promo) === config('company.promo-code')) {
            $specialOutfitterRole = Role::where('name', 'special_outfitter')->first();
            if ($specialOutfitterRole) {
                $user->roles()->attach($specialOutfitterRole);
            }
            $user->save();
        }
    }
}
