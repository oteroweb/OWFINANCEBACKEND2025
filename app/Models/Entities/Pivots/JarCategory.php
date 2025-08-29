<?php

namespace App\Models\Entities\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class JarCategory extends Pivot
{
    use SoftDeletes;

    protected $table = 'jar_category';

    protected $fillable = [
        'jar_id',
        'category_id',
        'active',
        'deleted_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deleted_at' => 'datetime:Y-m-d',
    ];
}
