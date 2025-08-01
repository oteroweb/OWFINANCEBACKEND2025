<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property float|null $last_price
 * @property int|null $tax_id
 * @property int $active
 * @property string|null $date
 * @property string|null $custom_name
 * @property int|null $item_category_id
 */
class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'last_price',
        'tax_id',
        'active',
        'date',
        'custom_name',
        'item_category_id',
    ];
}
