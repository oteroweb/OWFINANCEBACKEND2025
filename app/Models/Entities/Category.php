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
    'icon',
    'transaction_type_id',
    'include_in_balance',
    'type',
    'sort_order',
    ];

    protected $casts = [
        'date'       => 'datetime:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
    'include_in_balance' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionType()
    {
        return $this->belongsTo(TransactionType::class, 'transaction_type_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\CategoryFactory::new();
    }
}
