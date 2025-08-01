<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'active',
        'date',
        'deleted_at',
    ];
}
