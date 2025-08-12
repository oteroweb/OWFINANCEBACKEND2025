<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Models\Entities\Transaction;

class Provider extends Model
{
    use HasFactory, SoftDeletes, Notifiable;
    // protected $table      = 'providers';
    protected $fillable = [
        'name',
        'address',
        'active',
        'deleted_at',
        'email',
        'phone',
        'website',
        'description',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ProviderFactory::new();
    }
}
