<?php

namespace App\Models\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AiExtraction extends Model
{
    protected $fillable = [
        'user_id',
        'source',
        'raw_input',
        'extracted_data',
        'confidence_score',
        'was_confirmed',
        'transaction_id',
        'model_used',
        'input_tokens',
        'output_tokens',
        'cache_read_tokens',
        'processing_ms',
    ];

    protected $casts = [
        'extracted_data'   => 'array',
        'confidence_score' => 'float',
        'was_confirmed'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }
}
