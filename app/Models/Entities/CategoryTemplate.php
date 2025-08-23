<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_slug',
        'sort_order',
    ];
}
