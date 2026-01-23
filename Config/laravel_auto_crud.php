<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fallback Models Path
    |--------------------------------------------------------------------------
    |
    | This value is the fallback path of your models, which will be used when you
    | don't use --model-path option.
    |
    */
    'fallback_models_path' => 'app/Models/',

    /*
    |--------------------------------------------------------------------------
    | Inertia React Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for generating Inertia.js + React + TypeScript files
    | when using --type=inertia-react option.
    |
    */
    'inertia-react' => [
        /*
        | Path where React/TSX pages will be generated
        */
        'pages_path' => 'resources/js/pages',

        /*
        | Path where TypeScript type definitions will be generated
        */
        'types_path' => 'resources/js/types',

        /*
        | Use dialog-based forms instead of separate create/edit pages
        */
        'use_dialogs' => true,
    ],
];
