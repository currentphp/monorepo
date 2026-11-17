<?php

declare(strict_types=1);

namespace Current\Tests;

use Current\CurrentServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CurrentServiceProvider::class];
    }
}
