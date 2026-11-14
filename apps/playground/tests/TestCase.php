<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use WavyKit\Testing\InteractsWithWavyKit;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithWavyKit;
}
