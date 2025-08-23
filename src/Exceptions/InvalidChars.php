<?php

namespace Relaticle\Flowforge\Exceptions;

use InvalidArgumentException;
use Throwable;

use function implode;

class InvalidChars extends InvalidArgumentException
{
    private function __construct(string $message = '', int $code = 0, Throwable|null $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param non-empty-string       $rank
     * @param non-empty-list<string> $chars
     */
    public static function forInputRankWithInvalidChars(string $rank, array $chars): self
    {
        return new self('Rank provided contains an invalid Char. Rank Provided: ' . $rank . ' - Invalid char: ' . implode(', ', $chars));
    }
}
