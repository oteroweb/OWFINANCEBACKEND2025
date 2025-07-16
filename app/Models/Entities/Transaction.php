<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Transaction extends Model
{
    use SoftDeletes;
    use Notifiable;

    protected $fillable = [
        'name',
        'amount',
        'description',
        'date',
        'active',
        'deleted_at',
        'provider_id',
        'url_file',
        'rate_id',
        'amount_tax',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
        'date'       => 'datetime:Y-m-d H:i:s',
    ];
}
