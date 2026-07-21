<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;

class SharedTransactionCategory extends Model
{
    protected $fillable = [
        'transaction_id',
        'category_id',
        'amount',
        'jar_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function jar()
    {
        return $this->belongsTo(Jar::class);
    }
}
