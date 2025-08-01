<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'transaction_id',
        'quantity',
        'name',
        'amount',
        'tax_id',
        'rate_id',
        'description',
        'jar_id',
        'active',
        'deleted_at',
        'date',
        'category_id',
        'user_id',
        'custom_name',
    ];

    protected $casts = [
        'date'       => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ItemTransactionFactory::new();
    }
}
