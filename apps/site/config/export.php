<?php

declare(strict_types=1);

/*
 * Static export with spatie/laravel-export: `composer build` crawls the site
 * from "/" into dist/, which GitHub Pages serves.
 */
return [
    'crawl' => true,

    'use_streaming' => false,

    // Pages that no link reaches.
    'paths' => ['404.html', 'sitemap.xml'],

    'include_files' => [
        'public' => '',
    ],

    'exclude_file_patterns' => [
        '/\.php$/',
        '/\.htaccess$/',
        '/\/hot$/',
    ],

    'clean_before_export' => true,

    'disk' => null,

    'before' => [],

    'after' => [],
];
