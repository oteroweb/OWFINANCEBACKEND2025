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
        'adjustment',
        'refresh_mode',
        'allow_negative_balance',
        'negative_limit',
        'start_date',
        'use_global_start_date',
        'reset_cycle',
        'reset_cycle_day',
        'target_amount',
        'last_reset_date',
        'status',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',
        'start_date' => 'date:Y-m-d',
        'allow_negative_balance' => 'boolean',
        'use_global_start_date' => 'boolean',
        'reset_cycle_day' => 'integer',
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

    public function adjustments()
    {
        return $this->hasMany(JarAdjustment::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(JarWithdrawal::class);
    }

    public function cycles()
    {
        return $this->hasMany(JarCycle::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\JarFactory::new();
    }
}
