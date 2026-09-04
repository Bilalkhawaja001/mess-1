<?php
namespace App\Services\Payments;

use Illuminate\Support\Facades\DB;

/**
 * Read-only accessor for the alfa_payment_control app_settings row.
 * Fee percentages stay null until Bank Alfalah confirms them — never defaulted.
 */
class AlfaPaymentSettings
{
    private const KEY = 'alfa_payment_control';

    public function all(): array
    {
        $row = DB::table('app_settings')
            ->where('setting_key', self::KEY)
            ->where('is_active', 1)
            ->value('setting_value');

        return is_string($row) ? (json_decode($row, true) ?: []) : [];
    }

    public function providerEnabled(): bool
    {
        return (bool) config('alfa.enabled') && ($this->all()['provider_enabled'] ?? false) === true;
    }

    /** Method codes that are switched on in settings AND active in payment_methods. */
    public function availableMethods(): array
    {
        if (! $this->providerEnabled()) {
            return [];
        }

        $s = $this->all();
        $map = [
            'ALFA_WALLET' => 'wallet_enabled',
            'ALFA_BANK'   => 'bank_account_enabled',
            'ALFA_CARD'   => 'card_enabled',
        ];

        $wanted = [];
        foreach ($map as $code => $flag) {
            if (($s[$flag] ?? false) === true) {
                $wanted[] = $code;
            }
        }
        if ($wanted === []) {
            return [];
        }

        return DB::table('payment_methods')
            ->whereIn('code', $wanted)
            ->where('is_active', 1)
            ->pluck('code')
            ->all();
    }

    public function unavailableMessage(): string
    {
        return (string) ($this->all()['unavailable_message'] ?? 'Online payment is being activated.');
    }
}
