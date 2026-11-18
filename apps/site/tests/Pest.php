<?php

declare(strict_types=1);

use Current\Testing\InteractsWithCurrent;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(InteractsWithCurrent::class)->in('Feature');
