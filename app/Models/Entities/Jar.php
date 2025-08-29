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
        'percent',
        'type',
    'fixed_amount',
    'base_scope',
    'active',
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
        return $this->belongsToMany(Category::class, 'jar_category')
            ->using(\App\Models\Entities\Pivots\JarCategory::class)
            ->withPivot(['active', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    public function baseCategories()
    {
        return $this->belongsToMany(Category::class, 'jar_base_category')
            ->using(\App\Models\Entities\Pivots\JarBaseCategory::class)
            ->withPivot(['active', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    protected static function newFactory()
    {
        return \Database\Factories\JarFactory::new();
    }
}
