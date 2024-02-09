<?php

// config for JoshEmbling/Laragenie
return [
    'bot' => [
        'name' => 'Laragenie',
        'welcome' => 'Hello,
        I am Laragenie,
        how may I assist you today?',
        'instructions' => 'Write only in markdown format. Only write factual data that can be pulled from indexed chunks.',
    ],

    'chunks' => [
        'size' => 1000,
    ],

    'database' => [
        'fetch' => true,
        'save' => true,
    ],

    'extensions' => [
        'php',
        'blade.php',
        'js',
    ],

    'indexes' => [
        'directories' => [
            'App/Actions/**/*.php',
            'App/Console',
            'App/Exceptions',
            'App/Filament/**/*.php',
            'App/Http',
            'App/Livewire/**/*.php',
            'App/Livewire/*.php',
            'App/Models',
            'App/Notifications',
            'App/Policies',
            'App/Providers',
            './config/*.php',
            './database/**/*.php',
            './resources/**/*.php',
            './routes/*.php',
        ],
        'files' => [],
        'removal' => [
            'strict' => true,
        ],
    ],

    'openai' => [
        'embedding' => [
            'model' => 'text-embedding-ada-002',
            'max_tokens' => 5,
        ],
        'chat' => [
            'model' => 'gpt-4-1106-preview',
            'temperature' => 0.1,
        ],
    ],

    'pinecone' => [
        'topK' => 2,
    ],
];
