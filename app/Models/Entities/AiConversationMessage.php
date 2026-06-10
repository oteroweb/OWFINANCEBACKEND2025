<?php

namespace App\Models\Entities;

use Illuminate\Database\Eloquent\Model;

class AiConversationMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'input_tokens',
        'output_tokens',
        'cache_read_tokens',
        'cache_creation_tokens',
        'processing_ms',
    ];

    public function conversation()
    {
        return $this->belongsTo(AiConversation::class);
    }
}
