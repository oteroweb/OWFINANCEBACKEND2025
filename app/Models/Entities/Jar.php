<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Jar extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'is_active',
        'percent',
        'type',
        'active',
        'deleted_at',
        'date',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\JarFactory::new();
    }
}
