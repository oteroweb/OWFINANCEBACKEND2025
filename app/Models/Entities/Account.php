<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Entities\User;

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

    /**
     * The users that belong to the account.
     */
    public function users()
    {
    return $this->belongsToMany(User::class)->withTimestamps();
    }

    protected static function newFactory()
    {
        return \Database\Factories\AccountFactory::new();
    }
}
