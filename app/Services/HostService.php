<?php

namespace App\Services;

use App\Actions\Auth\RegisterUserAction;
use App\Actions\Host\RegisterHostAction;
use App\Actions\Host\ApplyPromoAction;
use App\Actions\StripeConnect\CreateStripeConnectAccountAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Propaganistas\LaravelPhone\PhoneNumber;

class HostService
{
    protected RegisterUserAction $registerUserAction;
    protected RegisterHostAction $registerHostAction;
    protected ApplyPromoAction $applyPromoAction;
    protected ?CreateStripeConnectAccountAction $createStripeConnectAccountAction;

    public function __construct(
        RegisterUserAction $registerUserAction,
        RegisterHostAction $registerHostAction,
        ApplyPromoAction $applyPromoAction,
        CreateStripeConnectAccountAction $createStripeConnectAccountAction = null
    ) {
        $this->registerUserAction = $registerUserAction;
        $this->registerHostAction = $registerHostAction;
        $this->applyPromoAction   = $applyPromoAction;
        $this->createStripeConnectAccountAction = $createStripeConnectAccountAction;
    }

    /**
     * Formats a phone number using the given country code.
     *
     * @param string $phone
     * @param string $country
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function formatPhoneNumber(string $phone, string $country): string
    {
        try {
            return (string) new PhoneNumber($phone, strtoupper($country));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid phone number format.');
        }
    }

    /**
     * Registers a new host with a new user and optional Stripe account.
     *
     * @param array $data
     * @return mixed
     */
    public function registerHost(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['phone_number'] = $this->formatPhoneNumber($data['phone_number'], $data['country']);

            $user = $this->registerUserAction->execute($data);
            $host = $this->registerHostAction->execute($user, $data);

            if (!empty($data['promo'])) {
                $this->applyPromoAction->execute($user, $data['promo']);
            }

            // Create a Stripe Connect account if banking details are provided and the action exists.
            if (!empty($data['account_number']) && $this->createStripeConnectAccountAction) {
                $this->createStripeConnectAccountAction->execute($host, $data);
            }

            return $host;
        });
    }

    /**
     * Upgrades an existing user to a host.
     *
     * @param User  $user
     * @param array $data
     * @return mixed
     */
    public function upgradeUserToHost(User $user, array $data)
    {
        return DB::transaction(function () use ($user, $data) {
            $formattedPhone = $this->formatPhoneNumber($data['phone_number'], $data['country']);
            $user->update(['phone_number' => $formattedPhone]);
            $data['phone_number'] = $formattedPhone;

            $host = $this->registerHostAction->execute($user, $data);

            if (!empty($data['promo'])) {
                $this->applyPromoAction->execute($user, $data['promo']);
            }

            return $host;
        });
    }
}
