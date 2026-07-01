<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Entities\Tag;

class ItemTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'transaction_id',
        'quantity',
        'name',
        'amount',
        'tax_id',
        'rate_id',
        'description',
        'jar_id',
        'active',
        'deleted_at',
        'date',
        'category_id',
        'item_category_id',
        'user_id',
        'custom_name',
        'is_fee',
        'fee_type',
    ];

    protected $casts = [
        'date'       => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d',
        'updated_at' => 'datetime:Y-m-d',
        'deleted_at' => 'datetime:Y-m-d',
        'is_fee'     => 'boolean',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\ItemTransactionFactory::new();
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }

    public function rate()
    {
        return $this->belongsTo(Rate::class);
    }

    public function jar()
    {
        return $this->belongsTo(Jar::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: an item transaction can have many tags
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'item_transaction_tags');
    }
}
