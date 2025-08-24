<?php

namespace Relaticle\Flowforge\Exceptions;

use InvalidArgumentException;
use Relaticle\Flowforge\Services\Rank;
use Throwable;

class PrevGreaterThanOrEquals extends InvalidArgumentException
{
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function betweenRanks(Rank $prev, Rank $next): self
    {
        return new self('Previous Rank (' . $prev->get() . ') is greater than or equals to Next (' . $next->get() . ')');
    }
}
