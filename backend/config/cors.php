<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    |
    | You can enable CORS for 1 or multiple paths.
    | Example: ['api/*']
    */
    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods
    |--------------------------------------------------------------------------
    |
    | Methods allowed in CORS requests.
    | Example: ['GET', 'POST', 'PUT', 'DELETE']
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | Specify the origins that are allowed to access your resources.
    | Example: ['http://localhost:3000']
    */
    'allowed_origins' => [
        'http://localhost:5173', // Vite dev server
        'http://localhost:8000', // Main app
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins Patterns
    |--------------------------------------------------------------------------
    |
    | You can use patterns to match origins.
    | Example: ['http://localhost:*']
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Headers
    |--------------------------------------------------------------------------
    |
    | Headers that are allowed in CORS requests.
    | Example: ['Content-Type', 'X-Auth-Token']
    */
    'allowed_headers' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Exposed Headers
    |--------------------------------------------------------------------------
    |
    | Headers that are exposed to the browser.
    | Example: ['Cache-Control', 'Content-Language']
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Max Age
    |--------------------------------------------------------------------------
    |
    | Specifies how long the results of a preflight request can be cached.
    |
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Supports Credentials
    |--------------------------------------------------------------------------
    |
    | Indicates whether or not the actual request can be made using credentials.
    |
    */
    'supports_credentials' => false,

];
