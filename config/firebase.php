<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Credentials
    |--------------------------------------------------------------------------
    |
    | Ruta al archivo JSON de credenciales de Firebase
    |
    */
    'credentials' => [
        'file' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/firebase-credentials.json')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | URL de tu base de datos de Firebase Realtime Database
    |
    */
    'database_url' => env('FIREBASE_DATABASE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Firebase Storage Bucket
    |--------------------------------------------------------------------------
    |
    | Bucket de Firebase Storage para almacenar archivos
    |
    */
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', ''),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | ID del proyecto de Firebase
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID', ''),
];
