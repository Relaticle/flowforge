<?php

namespace Relaticle\Flowforge\Services;

use Relaticle\Flowforge\Exceptions\InvalidChars;
use Relaticle\Flowforge\Exceptions\LastCharCantBeEqualToMinChar;
use Relaticle\Flowforge\Exceptions\MaxRankLength;
use Relaticle\Flowforge\Exceptions\PrevGreaterThanOrEquals;

use function array_filter;
use function array_values;
use function chr;
use function in_array;
use function ord;
use function str_split;
use function strcmp;
use function strlen;
use function substr;

/** @psalm-immutable  */
final class Rank
{
    public const MIN_CHAR = '0';

    public const MAX_CHAR = 'z';

    /** Usually, database like MySQL order using only the first 1024 chars */
    public const MAX_RANK_LEN = 1024;

    /**
     * @var non-empty-string
     *
     * @psalm-readonly
     */
    private string $rank;

    /**
     * @param  non-empty-string  $rank
     */
    private function __construct(string $rank)
    {
        self::rankValidator($rank);

        $this->rank = $rank;
    }

    /**
     * @param  non-empty-string  $rank
     */
    private static function rankValidator(string $rank): void
    {
        if (strlen($rank) > self::MAX_RANK_LEN) {
            throw MaxRankLength::forInputRank($rank, self::MAX_RANK_LEN);
        }

        $invalidChars = array_filter(
            str_split($rank),
            static function ($char) {
                return ord($char) < ord(self::MIN_CHAR) || ord($char) > ord(self::MAX_CHAR);
            }
        );

        if ($invalidChars !== []) {
            throw InvalidChars::forInputRankWithInvalidChars($rank, array_values($invalidChars));
        }

        $lastChar = substr($rank, -1);
        if ($lastChar === self::MIN_CHAR) {
            throw LastCharCantBeEqualToMinChar::forRank($rank, self::MIN_CHAR);
        }
    }

    /**
     * @return non-empty-string
     */
    public function get(): string
    {
        return $this->rank;
    }

    /**
     * @param  non-empty-string  $rank
     */
    public static function fromString(string $rank): self
    {
        return new self($rank);
    }

    public static function forEmptySequence(): self
    {
        return self::fromString(self::mid(self::MIN_CHAR, self::MAX_CHAR));
    }

    public static function after(self $prevRank): self
    {
        $char = substr($prevRank->get(), -1);

        if (ord($char) + 1 >= ord(self::MAX_CHAR)) {
            return self::fromString(
                $prevRank->get() . chr(ord(self::MIN_CHAR) + 1)
            );
        }

        $return = substr($prevRank->get(), 0, -1) . chr(ord($char) + 1);

        self::ensureNonEmpty($return, 'after(): computed $return');

        return self::fromString($return);
    }

    public static function before(self $nextRank): self
    {
        $char = substr($nextRank->get(), -1);

        if (ord($char) - 1 <= ord(self::MIN_CHAR)) {
            $return = substr($nextRank->get(), 0, -1) . chr(ord($char) - 1) . chr(ord(self::MAX_CHAR) - 1);

            self::ensureNonEmpty($return, 'before(): computed $return (edge case)');

            return self::fromString($return);
        }

        $return = substr($nextRank->get(), 0, -1) . chr(ord($char) - 1);

        self::ensureNonEmpty($return, 'before(): computed $return');

        return self::fromString($return);
    }

    public static function betweenRanks(self $prevRank, self $nextRank): self
    {
        if (strcmp($prevRank->get(), $nextRank->get()) >= 0) {
            throw PrevGreaterThanOrEquals::betweenRanks($prevRank, $nextRank);
        }

        $rank = '';
        $i = 0;
        while ($i <= self::MAX_RANK_LEN) {
            $prevChar = $prevRank->getChar($i, self::MIN_CHAR);
            $nextChar = $nextRank->getChar($i, self::MAX_CHAR);
            $i++;

            $midChar = self::mid($prevChar, $nextChar);
            if (in_array($midChar, [$prevChar, $nextChar])) {
                $rank .= $prevChar;

                continue;
            }

            $rank .= $midChar;

            break;
        }

        self::ensureNonEmpty($rank, 'betweenRanks(): computed $rank');

        return self::fromString($rank);
    }

    /**
     * @param  0|positive-int  $i
     * @param  non-empty-string  $defaultChar
     * @return non-empty-string
     */
    private function getChar(int $i, string $defaultChar): string
    {
        $return = $this->rank[$i] ?? $defaultChar;

        self::ensureNonEmpty($return, 'getChar(): selected $return');

        return $return;
    }

    /**
     * @param  non-empty-string  $prev
     * @param  non-empty-string  $next
     * @return non-empty-string
     *
     * @psalm-pure
     */
    private static function mid(string $prev, string $next): string
    {
        if (ord($prev) >= ord($next)) {
            return $prev;
        }

        $return = chr((int) ((ord($prev) + ord($next)) / 2));

        self::ensureNonEmpty($return, 'mid(): computed $return');

        return $return;
    }

    /**
     * Ensure a string is non-empty at runtime; avoids relying on Webmozart Assert in production.
     *
     * @psalm-assert non-empty-string $value
     */
    private static function ensureNonEmpty(string $value, string $context): void
    {
        if ($value === '') {
            throw new \LogicException('Rank invariant violated: ' . $context . ' must be a non-empty string.');
        }
    }
}
