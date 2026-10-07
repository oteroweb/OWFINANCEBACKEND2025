<?php

namespace App\Models\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BusinessUser extends Model
{
    protected $fillable = ['business_id', 'user_id', 'role', 'status', 'invited_by'];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
