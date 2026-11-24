<?php

declare(strict_types=1);

namespace Current\Tests\Inertia;

use Current\CurrentServiceProvider;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CurrentServiceProvider::class, InertiaServiceProvider::class];
    }
}
