<?php

namespace App\Models\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $table = 'ai_usage_log';

    protected $fillable = [
        'user_id',
        'feature',
        'provider_name',
        'model_used',
        'input_tokens',
        'output_tokens',
        'cache_read_tokens',
        'cache_creation_tokens',
        'estimated_cost_usd',
        'date',
    ];

    protected $casts = [
        'date'               => 'date',
        'estimated_cost_usd' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
