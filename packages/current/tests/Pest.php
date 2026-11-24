<?php

declare(strict_types=1);
use Current\Tests\Inertia\TestCase as InertiaTestCase;
use Current\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
// The Inertia adapter's tests also boot Inertia's service provider.
pest()->extend(InertiaTestCase::class)->in('Inertia/Feature');
