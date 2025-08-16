<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role_id',
        'balance',
        'currency_id',
        'client_id',
        'active',
    ];
    /**
     * The accounts that belong to the user.
     */
    public function accounts()
    {
        return $this->belongsToMany(\App\Models\Entities\Account::class, 'account_user', 'user_id', 'account_id')
            ->withPivot('is_owner')
            ->withTimestamps()
            ->select('accounts.*', 'account_user.is_owner as is_owner');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    'pivot',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin()
    {
        return $this->role && $this->role->slug === 'admin';
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Entities\Client::class);
    }
    public function currency()
    {
        return $this->belongsTo(\App\Models\Entities\Currency::class);
    }
}
