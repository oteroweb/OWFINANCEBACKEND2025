<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $account_id
 * @property int $tax_id
 * @property float|null $amount
 * @property float|null $percent
 * @property int $active
 */
class AccountTax extends Model
{
    protected $table = 'accounts_taxes';
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'tax_id',
        'amount',
        'percent',
        'active',
    ];
}
