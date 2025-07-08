<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Currency extends Model
{
    use SoftDeletes;
    use Notifiable;
    // protected $table      = 'currencies';
    protected $fillable = [
            'name',
            'symbol',
            'align',
            'rounding',
            'name_plural',
            'code',
            'active',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

    // public function customer()
    // {
    //     return $this->hasOne('App\Http\Models\Entities\Customer');
    // }
    // public function account()
    // {
    //     return $this->hasOne('App\Http\Models\Entities\Account');
    // }
}
