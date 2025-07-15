<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'currency_id',
        'initial',
        'account_type_id',
        'active',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }
}
