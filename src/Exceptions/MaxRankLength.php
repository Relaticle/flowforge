<?php

namespace Relaticle\Flowforge\Exceptions;

use InvalidArgumentException;
use Throwable;

use function strlen;

class MaxRankLength extends InvalidArgumentException
{
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param  non-empty-string  $rank
     */
    public static function forInputRank(string $rank, int $maxRankLength): self
    {
        return new self('The length of Rank provided is too long. Rank Provided: ' . $rank . ' - Rank Length: ' . strlen($rank) . ' - Max length: ' . $maxRankLength);
    }
}
