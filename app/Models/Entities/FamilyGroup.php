<?php

namespace App\Models\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'owner_user_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members()
    {
        return $this->hasMany(FamilyGroupMember::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\FamilyGroupFactory::new();
    }
}
