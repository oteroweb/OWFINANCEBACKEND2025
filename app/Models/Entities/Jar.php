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
    'fixed_amount',
    'base_scope',
        'active',
        'deleted_at',
        'date',
    'user_id',
    'sort_order',
    'color',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'jar_category')->withTimestamps();
    }

    public function baseCategories()
    {
        return $this->belongsToMany(Category::class, 'jar_base_category')->withTimestamps();
    }

    protected static function newFactory()
    {
        return \Database\Factories\JarFactory::new();
    }
}
