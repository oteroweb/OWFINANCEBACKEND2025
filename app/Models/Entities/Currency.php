<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Currency extends Model
{
    use SoftDeletes;
    use Notifiable;
    use HasFactory;
    protected $fillable = [
            'name',
            'symbol',
            'align',
            // 'rounding',
            // 'name_plural',
            'code',
            'active',
            'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\CurrencyFactory::new();
    }
}
