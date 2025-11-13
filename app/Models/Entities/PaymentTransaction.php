<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $transaction_id
 * @property int $account_id
 * @property float $amount
 * @property int $active
 */
class PaymentTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'account_id',
        'user_currency_id',
        'amount',
        'active',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Account used for this payment movement.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * User-specific currency rate used for this payment.
     */
    public function userCurrency()
    {
        return $this->belongsTo(UserCurrency::class, 'user_currency_id');
    }

    /**
     * Alias relation: expose the rate object as 'rate' for API consumers.
     */
    public function rate()
    {
        return $this->belongsTo(UserCurrency::class, 'user_currency_id');
    }

    /**
     * Convenience appended attributes for API responses.
     * - account_name: the related account name
     * - rate_id: alias of user_currency_id (what client expects)
     * - rate_value: numeric rate (current_rate) from related user currency record
     * - rate_is_current / rate_is_official: flags from related user currency (flattened)
     */
    protected $appends = [
        'account_name',
        'rate_id',
        'rate_value',
        'rate_is_current',
        'rate_is_official',
    ];

    public function getAccountNameAttribute()
    {
        return optional($this->account)->name;
    }

    public function getRateIdAttribute(): ?int
    {
        return $this->user_currency_id ?: null;
    }

    public function getRateValueAttribute(): ?float
    {
        return optional($this->userCurrency)->current_rate;
    }

    public function getRateIsCurrentAttribute(): ?bool
    {
        return optional($this->userCurrency)->is_current;
    }

    public function getRateIsOfficialAttribute(): ?bool
    {
        return optional($this->userCurrency)->is_official;
    }
}
