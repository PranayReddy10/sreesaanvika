<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
         * Photographs and films the shop uploads.
         *
         * Normally these live outside the web root and are reached through the
         * symlink `php artisan storage:link` makes. Some shared hosts forbid
         * symlinks outright — Hostinger disables PHP's symlink() and exec(),
         * so that command cannot run at all — and on a few the web server will
         * not follow one even if it exists.
         *
         * Set SHOP_UPLOADS_IN_PUBLIC=true there and uploads are written
         * straight into public/uploads instead, which needs no symlink. The
         * paths stored against each saree are relative to this root either
         * way, so nothing in the database changes; only the files move.
         */
        'public' => [
            'driver' => 'local',
            'root' => env('SHOP_UPLOADS_IN_PUBLIC', false)
                ? public_path('uploads')
                : storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/')
                .(env('SHOP_UPLOADS_IN_PUBLIC', false) ? '/uploads' : '/storage'),
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
