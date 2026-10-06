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
        'public' => env('SHOP_UPLOADS_ON_SPACES', false) ? [
            /*
             * Off the shop's own disk altogether.
             *
             * DigitalOcean Spaces speaks S3, so this is the s3 driver pointed
             * at their endpoint. Worth it when the shop outgrows a shared
             * host's disk, or wants its photographs served from a CDN rather
             * than from one box in one data centre — and it ends the symlink
             * business below for good, because nothing is stored locally to
             * link to.
             *
             * The paths against each saree are the same either way, so moving
             * is copying the files up and setting this to true. Nothing in the
             * database changes.
             */
            'driver' => 's3',
            'key' => env('SPACES_KEY'),
            'secret' => env('SPACES_SECRET'),
            'region' => env('SPACES_REGION', 'blr1'),
            'bucket' => env('SPACES_BUCKET'),
            'endpoint' => env('SPACES_ENDPOINT', 'https://'.env('SPACES_REGION', 'blr1').'.digitaloceanspaces.com'),
            // The CDN address where there is one, so a saree photograph is
            // served from the edge rather than from Bangalore every time.
            'url' => rtrim((string) env('SPACES_URL', ''), '/') ?: null,
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ] : [
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
