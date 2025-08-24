<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * Base exception class for all Flowforge-specific exceptions.
 * Provides consistent exception handling across the plugin.
 */
abstract class FlowforgeException extends InvalidArgumentException
{
    protected function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
