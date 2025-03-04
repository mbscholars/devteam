<?php

// config for mbscholars/Devteam
return [
    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    |
    | API keys for various services used by the package.
    |
    */
    'api_keys' => [
        'openai' => env('OPENAI_API_KEY'),
        'anthropic' => env('ANTHROPIC_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Summary Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the app:summary and app:frontend-summary commands.
    |
    */
    'summary' => [
        // Default output paths for summary files
        'backend_output_path' => 'app/context/summary.json',
        'frontend_output_path' => 'app/context/frontend-summary.json',
        
        // Directories to ignore when scanning the application
        'ignored_directories' => [
            'migrations',
            'resources/views',
            'node_modules',
            'vendor',
            'storage',
            'bootstrap/cache',
            'public',
            'tests',
        ],
        
        // Additional directories to scan for backend components
        'backend_scan_directories' => [
            
        ],
        
        // Additional directories to scan for frontend components
        'frontend_scan_directories' => [
            
        ],
        
        // Maximum file size to analyze (in KB)
        'max_file_size' => 500,
        
        // Maximum depth for directory scanning
        'max_scan_depth' => 5,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | AI Context Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI context generation and usage.
    |
    */
    'ai_context' => [
        // Whether to include database schema in the context
        'include_db_schema' => true,
        
        // Whether to include route information in the context
        'include_routes' => true,
        
        // Maximum tokens to use for context
        'max_context_tokens' => 4000,
        
        // Context refresh interval in minutes
        'context_refresh_interval' => 60,
    ],
];
