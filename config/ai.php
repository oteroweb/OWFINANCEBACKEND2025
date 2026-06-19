<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Feature → Provider mapping
    |--------------------------------------------------------------------------
    | Set AI_EXTRACTION_PROVIDER and AI_ADVISOR_PROVIDER in .env
    | Options: anthropic | gemini | openai | groq | opencode-go
    */
    'features' => [
        'extraction' => env('AI_EXTRACTION_PROVIDER', 'anthropic'),
        'advisor'    => env('AI_ADVISOR_PROVIDER', 'anthropic'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider credentials & model selection
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'anthropic' => [
            'key'  => env('ANTHROPIC_API_KEY'),
            'models' => [
                'extraction' => env('AI_ANTHROPIC_EXTRACTION_MODEL', 'claude-haiku-4-5'),
                'advisor'    => env('AI_ANTHROPIC_ADVISOR_MODEL', 'claude-sonnet-4-5'),
            ],
            'pricing' => [ // USD per 1M tokens
                'input'        => 0.80,
                'output'       => 4.00,
                'cache_read'   => 0.08,
                'cache_write'  => 1.00,
            ],
        ],

        'gemini' => [
            'key'  => env('GEMINI_API_KEY'),
            'models' => [
                'extraction' => env('AI_GEMINI_EXTRACTION_MODEL', 'gemini-2.0-flash'),
                'advisor'    => env('AI_GEMINI_ADVISOR_MODEL', 'gemini-2.0-flash'),
            ],
            'pricing' => [
                'input'       => 0.075,
                'output'      => 0.30,
                'cache_read'  => 0.018,
                'cache_write' => 0.075,
            ],
        ],

        'openai' => [
            'key'  => env('OPENAI_API_KEY'),
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

        'opencode-go' => [
            'key'      => env('OPENCODE_GO_API_KEY'),
            'base_url' => 'https://opencode.ai/zen/go/v1',
            'models'   => [
                'extraction' => env('AI_OPENCODE_EXTRACTION_MODEL', 'deepseek-v4-flash'),
                'advisor'    => env('AI_OPENCODE_ADVISOR_MODEL', 'deepseek-v4-flash'),
            ],
            'pricing' => [ // flat $10/mes, costo por token = 0
                'input'       => 0.00,
                'output'      => 0.00,
                'cache_read'  => 0.00,
                'cache_write' => 0.00,
            ],
        ],

        'groq' => [
            'key'  => env('GROQ_API_KEY'),
            'models' => [
                'extraction' => env('AI_GROQ_EXTRACTION_MODEL', 'llama-3.3-70b-versatile'),
                'advisor'    => env('AI_GROQ_ADVISOR_MODEL', 'llama-3.3-70b-versatile'),
            ],
            'pricing' => [
                'input'       => 0.59,
                'output'      => 0.79,
                'cache_read'  => 0.00,
                'cache_write' => 0.00,
            ],
        ],
    ],
];
