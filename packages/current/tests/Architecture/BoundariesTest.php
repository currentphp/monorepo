<?php

declare(strict_types=1);

use Current\Actions\Actions;
use Current\Context;
use Current\CurrentServiceProvider;
use Current\Experience;
use Current\Inertia\CurrentProps;
use Current\Navigation\Navigation;
use Current\Pages\Pages;
use Current\Subjects\SubjectResolver;
use Current\Subjects\Subjects;
use Current\Support\DestinationUrls;

// Laravel at the edges: builders, definitions, registries, and outputs make no
// Laravel calls; readers, the request context, providers, facades, and console
// commands hold that code. SubjectResolver and DestinationUrls are internal
// Laravel readers; builders, registries, and outputs retain the same restrictions.
arch('only the edges use Laravel')
    ->expect('Current')
    ->not->toUse('Illuminate')
    ->ignoring([
        Context::class,
        CurrentServiceProvider::class,
        Experience::class,
        'Current\Facades',
        'Current\Console',
        Pages::class,
        Navigation::class,
        Subjects::class,
        SubjectResolver::class,
        DestinationUrls::class,
        Actions::class,
        CurrentProps::class,
        'Current\Tests',
    ]);

// Inertia is an optional dependency: only the adapter may reference it, so
// applications without inertiajs/inertia-laravel never load an Inertia class.
arch('only the Inertia adapter uses Inertia')
    ->expect('Current')
    ->not->toUse('Inertia')
    ->ignoring(['Current\Inertia', 'Current\Tests']);

arch('source files declare strict types')
    ->expect('Current')
    ->toUseStrictTypes()
    ->ignoring('Current\Tests');
