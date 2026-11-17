<?php

declare(strict_types=1);

namespace Current\Exceptions;

use Throwable;

/** Marker for every exception Current throws; catch it to handle all Current errors. */
interface CurrentException extends Throwable {}
