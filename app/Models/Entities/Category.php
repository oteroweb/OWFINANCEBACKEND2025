<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Category extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'active',
        'date',
        'parent_id',
    'user_id',
    ];

    protected $casts = [
        'date'       => 'datetime:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\CategoryFactory::new();
    }
}
