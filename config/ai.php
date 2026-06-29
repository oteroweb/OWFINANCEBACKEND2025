<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Feature → Provider mapping
    |--------------------------------------------------------------------------
    | Primary provider + comma-separated fallback chain (tried in order if
    | the primary fails or has no key).
    |
    | Options: opencode-go | groq | openrouter | gemini | xai | openai | anthropic
    |
    | Recommended priority (cheapest first):
    |   extraction: opencode-go → groq → openrouter → gemini → xai → openai
    |   advisor:    opencode-go → gemini → openrouter → groq → xai → openai
    */
    'features' => [
        'extraction'          => env('AI_EXTRACTION_PROVIDER', 'opencode-go'),
        'extraction_fallback' => env('AI_EXTRACTION_FALLBACK', 'groq,openrouter,gemini,xai,openai'),
        'advisor'             => env('AI_ADVISOR_PROVIDER', 'opencode-go'),
        'advisor_fallback'    => env('AI_ADVISOR_FALLBACK', 'gemini,openrouter,groq,xai,openai'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider credentials & model selection
    |--------------------------------------------------------------------------
    */
    'providers' => [

        'opencode-go' => [
            'key'      => env('OPENCODE_GO_API_KEY'),
            'base_url' => 'https://opencode.ai/zen/go/v1',
            'label'    => 'OpenCode Zen',
            'models'   => [
                'extraction' => env('AI_OPENCODE_EXTRACTION_MODEL', 'deepseek-v4-flash'),
                'advisor'    => env('AI_OPENCODE_ADVISOR_MODEL', 'deepseek-v4-flash'),
            ],
            'pricing' => [ // $10/mes fijo, costo por token = 0
                'input'       => 0.00,
                'output'      => 0.00,
                'cache_read'  => 0.00,
                'cache_write' => 0.00,
            ],
        ],

        'groq' => [
            'key'   => env('GROQ_API_KEY'),
            'label' => 'Groq',
            'models' => [
                'extraction' => env('AI_GROQ_EXTRACTION_MODEL', 'llama-3.3-70b-versatile'),
                'advisor'    => env('AI_GROQ_ADVISOR_MODEL', 'llama-3.3-70b-versatile'),
            ],
            'pricing' => [ // USD per 1M tokens (free tier: 14,400 req/día)
                'input'       => 0.59,
                'output'      => 0.79,
                'cache_read'  => 0.00,
                'cache_write' => 0.00,
            ],
        ],

        'openrouter' => [
            'key'      => env('OPENROUTER_API_KEY'),
            'base_url' => 'https://openrouter.ai/api/v1',
            'label'    => 'OpenRouter',
            'models'   => [
                'extraction' => env('AI_OPENROUTER_EXTRACTION_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
                'advisor'    => env('AI_OPENROUTER_ADVISOR_MODEL', 'meta-llama/llama-3.3-70b-instruct:free'),
            ],
            'pricing' => [ // modelos :free = $0; otros varía
                'input'       => 0.00,
                'output'      => 0.00,
                'cache_read'  => 0.00,
                'cache_write' => 0.00,
            ],
        ],

        'gemini' => [
            'key'   => env('GEMINI_API_KEY'),
            'label' => 'Google Gemini',
            'models' => [
                'extraction' => env('AI_GEMINI_EXTRACTION_MODEL', 'gemini-2.0-flash'),
                'advisor'    => env('AI_GEMINI_ADVISOR_MODEL', 'gemini-2.0-flash'),
            ],
            'pricing' => [ // gratis hasta 1M tokens/día en free tier
                'input'       => 0.075,
                'output'      => 0.30,
                'cache_read'  => 0.018,
                'cache_write' => 0.075,
            ],
        ],

        'xai' => [
            'key'      => env('XAI_API_KEY'),
            'base_url' => 'https://api.x.ai/v1',
            'label'    => 'xAI Grok',
            'models'   => [
                'extraction' => env('AI_XAI_EXTRACTION_MODEL', 'grok-3-mini'),
                'advisor'    => env('AI_XAI_ADVISOR_MODEL', 'grok-3-mini'),
            ],
            'pricing' => [ // USD per 1M tokens
                'input'       => 0.30,
                'output'      => 0.50,
                'cache_read'  => 0.00,
                'cache_write' => 0.00,
            ],
        ],

        'openai' => [
            'key'   => env('OPENAI_API_KEY'),
            'label' => 'OpenAI',
            'models' => [
                'extraction' => env('AI_OPENAI_EXTRACTION_MODEL', 'gpt-4o-mini'),
                'advisor'    => env('AI_OPENAI_ADVISOR_MODEL', 'gpt-4o-mini'),
            ],
            'pricing' => [
                'input'       => 0.15,
                'output'      => 0.60,
                'cache_read'  => 0.075,
                'cache_write' => 0.15,
            ],
        ],

        'anthropic' => [
            'key'   => env('ANTHROPIC_API_KEY'),
            'label' => 'Anthropic',
            'models' => [
                'extraction' => env('AI_ANTHROPIC_EXTRACTION_MODEL', 'claude-haiku-4-5'),
                'advisor'    => env('AI_ANTHROPIC_ADVISOR_MODEL', 'claude-sonnet-4-5'),
            ],
            'pricing' => [
                'input'        => 0.80,
                'output'       => 4.00,
                'cache_read'   => 0.08,
                'cache_write'  => 1.00,
            ],
        ],
    ],
];
