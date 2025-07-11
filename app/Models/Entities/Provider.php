<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Provider extends Model
{
    use SoftDeletes;
    use Notifiable;
    // protected $table      = 'providers';
    protected $fillable = [
            'name',
            'address',
            'active',
            'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

}
