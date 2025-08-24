<?php

namespace Relaticle\Flowforge\Exceptions;

use function implode;

class InvalidChars extends FlowforgeException
{
    /**
     * @param  non-empty-string  $rank
     * @param  non-empty-list<string>  $chars
     */
    public static function forInputRankWithInvalidChars(string $rank, array $chars): self
    {
        return new self('Rank provided contains an invalid Char. Rank Provided: ' . $rank . ' - Invalid char: ' . implode(', ', $chars));
    }
}
