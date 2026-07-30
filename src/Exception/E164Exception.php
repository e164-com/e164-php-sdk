<?php

declare(strict_types=1);

namespace E164\Exception;

use Throwable;

/**
 * Implemented by every exception this SDK throws, so callers can catch
 * anything originating from the SDK with a single catch block.
 */
interface E164Exception extends Throwable
{
}
