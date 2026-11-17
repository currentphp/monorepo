<?php

declare(strict_types=1);

use Current\Context;
use Current\CurrentServiceProvider;
use Current\Experience;
use Current\Navigation\Navigation;
use Current\Pages\Pages;
use Current\Subjects\Subjects;

// Laravel at the edges: builders, definitions, registries, and outputs make no
// Laravel calls; readers, the request context, providers, facades, and console
// commands hold that code.
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
        'Current\Tests',
    ]);

arch('source files declare strict types')
    ->expect('Current')
    ->toUseStrictTypes()
    ->ignoring('Current\Tests');
