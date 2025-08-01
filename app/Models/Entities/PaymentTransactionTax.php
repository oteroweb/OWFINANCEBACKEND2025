<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $payment_transaction_id
 * @property int $tax_id
 * @property float $amount
 * @property float|null $percent
 * @property int $active
 */
class PaymentTransactionTax extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_transaction_id',
        'tax_id',
        'amount',
        'percent',
        'active',
    ];
}
