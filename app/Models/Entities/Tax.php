<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tax extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
    'name',
    'percent',
    'applies_to', // item|payment|both
    'active',
    'date',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\TaxFactory::new();
    }
}
