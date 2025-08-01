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
        'amount',
        'active',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
