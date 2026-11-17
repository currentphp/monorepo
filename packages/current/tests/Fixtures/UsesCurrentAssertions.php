<?php

declare(strict_types=1);

namespace Current\Tests\Fixtures;

use Current\Testing\InteractsWithCurrent;
use Current\Tests\TestCase;

/** Lets PHPStan analyse the assertions trait, which no class in src uses. */
abstract class UsesCurrentAssertions extends TestCase
{
    use InteractsWithCurrent;
}
