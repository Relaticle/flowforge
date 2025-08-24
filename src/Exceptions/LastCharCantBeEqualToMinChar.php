<?php

namespace Relaticle\Flowforge\Exceptions;

use InvalidArgumentException;
use Throwable;

class LastCharCantBeEqualToMinChar extends InvalidArgumentException
{
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param  non-empty-string  $rank
     */
    public static function forRank(string $rank, string $minChar): self
    {
        return new self('The last char of the rank (' . $rank . ') can\'t be equal to the min char (' . $minChar . ').');
    }
}
