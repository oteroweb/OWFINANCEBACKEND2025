<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemTax extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_transaction_id',
        'tax_id',
        'amount',
        'percent',
        'active',
        'deleted_at',
        'date',
    ];
}
