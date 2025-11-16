<?php

namespace App\Services;

use App\Models\Entities\UserCurrency;

class UserRateService
{
    /**
     * Apply a rate coming from a payment to the user's currency settings.
     * Policy:
     * - Current: keep a single current per (user,currency). If $makeCurrent, clear previous is_current and mark this one current.
     * - Official: act as historical marker. If $makeOfficial, DO NOT clear previous; just mark this one official and timestamp it.
     */
    public function applyFromPayment(int $userId, int $currencyId, float $rate, bool $makeCurrent = false, bool $makeOfficial = false): UserCurrency
    {
        // Reuse existing record for this exact rate if present; otherwise create a new one
        $record = UserCurrency::firstOrCreate(
            [
                'user_id' => $userId,
                'currency_id' => $currencyId,
                'current_rate' => $rate,
            ],
            [
                'is_current' => false,
                'is_official' => false,
            ]
        );

        if ($makeCurrent) {
            // Ensure single current per (user,currency)
            UserCurrency::where('user_id', $userId)
                ->where('currency_id', $currencyId)
                ->update(['is_current' => false]);
            $record->is_current = true;
        }
        if ($makeOfficial) {
            // Historical flag: do not demote previous officials
            $record->is_official = true;
            if (empty($record->official_at)) {
                $record->official_at = now();
            }
        }
        $record->save();
        return $record;
    }
}
