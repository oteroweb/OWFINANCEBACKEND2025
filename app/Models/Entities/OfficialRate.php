<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Insert-only history of officially fetched currency rates (e.g. BCV via ve.dolarapi.com).
 * Used as a fallback source by TransactionController::resolveUserCurrencyRate()
 * when the user has no UserCurrency marked is_current for that currency.
 */
class OfficialRate extends Model
{
    protected $fillable = [
        'currency_id',
        'rate',
        'source',
        'fetched_at',
    ];

    protected $casts = [
        'rate' => 'float',
        'fetched_at' => 'datetime',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
