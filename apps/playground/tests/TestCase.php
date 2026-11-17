<?php

namespace Tests;

use Current\Testing\InteractsWithCurrent;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithCurrent;
}
