<?php

namespace App\Services;

use App\Models\Entities\UserCurrency;

class UserRateService
{
    /**
     * Apply a rate coming from a payment to the user's currency settings.
     * - If $makeCurrent, clear previous is_current for (user,currency) and mark the new one current
     * - If $makeOfficial, clear previous is_official for (user,currency) and mark the new one official
     */
    public function applyFromPayment(int $userId, int $currencyId, float $rate, bool $makeCurrent = false, bool $makeOfficial = false): UserCurrency
    {
        if ($makeCurrent) {
            UserCurrency::where('user_id', $userId)
                ->where('currency_id', $currencyId)
                ->update(['is_current' => false]);
        }
        if ($makeOfficial) {
            UserCurrency::where('user_id', $userId)
                ->where('currency_id', $currencyId)
                ->update(['is_official' => false]);
        }

        return UserCurrency::create([
            'user_id' => $userId,
            'currency_id' => $currencyId,
            'current_rate' => $rate,
            'is_current' => $makeCurrent,
            'is_official' => $makeOfficial,
        ]);
    }
}
