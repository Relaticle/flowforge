<?php

declare(strict_types=1);

namespace Relaticle\Flowforge\Services;

/**
 * Decimal-based position calculation using BCMath for precision.
 * Uses DECIMAL(20,10) storage - 10 integer digits + 10 decimal places.
 *
 * This replaces the string-based Rank service with a mathematically
 * sound approach that never throws exceptions on midpoint calculations.
 *
 * Includes jitter for concurrent safety - each call to between() produces
 * a unique position to prevent collisions when multiple users move cards
 * to the same position simultaneously.
 */
final class DecimalPosition
{
    /**
     * Default gap between positions (65,535).
     * This allows ~16 bisections before gap < 1, and with 10 decimal places,
     * effectively ~33+ bisections before hitting MIN_GAP.
     */
    public const DEFAULT_GAP = '65535';

    /**
     * Minimum gap that triggers rebalancing.
     * When gap between adjacent positions falls below this, auto-rebalance.
     */
    public const MIN_GAP = '0.0001';

    /**
     * BCMath scale (decimal places) for all calculations.
     * Matches DECIMAL(20,10) database column.
     */
    public const SCALE = 10;

    /**
     * Jitter factor - percentage of available gap to use for randomization.
     * 0.1 = 10% of the gap, meaning jitter range is ±5% around the midpoint.
     * This prevents collisions when concurrent users insert at the same position.
     */
    public const JITTER_FACTOR = '0.1';

    /**
     * Get the initial position for an empty column.
     */
    public static function forEmptyColumn(): string
    {
        return self::DEFAULT_GAP;
    }

    /**
     * Calculate position after a given position (for appending).
     */
    public static function after(string $position): string
    {
        return bcadd($position, self::DEFAULT_GAP, self::SCALE);
    }

    /**
     * Calculate position before a given position (for prepending).
     */
    public static function before(string $position): string
    {
        return bcsub($position, self::DEFAULT_GAP, self::SCALE);
    }

    /**
     * Calculate position between two bounds WITH JITTER for concurrent safety.
     *
     * Jitter prevents position collisions when multiple users simultaneously
     * move cards to the same position. Each call produces a unique value
     * near the midpoint but with cryptographically secure randomization.
     *
     * @param  string  $after  Lower bound position (card above)
     * @param  string  $before  Upper bound position (card below)
     * @return string  Position between bounds with jitter applied
     */
    public static function between(string $after, string $before): string
    {
        // Calculate the exact midpoint
        $sum = bcadd($after, $before, self::SCALE);
        $midpoint = bcdiv($sum, '2', self::SCALE);

        // Calculate the gap between positions
        $gap = bcsub($before, $after, self::SCALE);

        // Jitter range: ±(gap * JITTER_FACTOR / 2)
        // This gives us ±5% of the gap on each side of midpoint
        $jitterRange = bcmul($gap, self::JITTER_FACTOR, self::SCALE);
        $halfJitter = bcdiv($jitterRange, '2', self::SCALE);

        // Generate cryptographically secure random jitter
        $jitter = self::generateJitter($halfJitter);

        return bcadd($midpoint, $jitter, self::SCALE);
    }

    /**
     * Calculate exact midpoint between two positions WITHOUT jitter.
     *
     * Use this for deterministic testing or when exact midpoint is required.
     * For production use, prefer between() which adds jitter for concurrent safety.
     *
     * @param  string  $after  Lower bound position
     * @param  string  $before  Upper bound position
     * @return string  Exact midpoint between bounds
     */
    public static function betweenExact(string $after, string $before): string
    {
        $sum = bcadd($after, $before, self::SCALE);

        return bcdiv($sum, '2', self::SCALE);
    }

    /**
     * Calculate position based on adjacent card positions.
     *
     * @param  string|null  $afterPos  Position of card above (null = insert at top)
     * @param  string|null  $beforePos  Position of card below (null = insert at bottom)
     */
    public static function calculate(?string $afterPos, ?string $beforePos): string
    {
        // Empty column - return default position
        if ($afterPos === null && $beforePos === null) {
            return self::forEmptyColumn();
        }

        // Insert between two cards - midpoint (never fails!)
        if ($afterPos !== null && $beforePos !== null) {
            return self::between($afterPos, $beforePos);
        }

        // Insert at top - subtract gap
        if ($beforePos !== null) {
            return self::before($beforePos);
        }

        // Insert at bottom - add gap
        return self::after($afterPos);
    }

    /**
     * Check if gap between positions is too small and needs rebalancing.
     */
    public static function needsRebalancing(string $afterPos, string $beforePos): bool
    {
        $gap = bcsub($beforePos, $afterPos, self::SCALE);

        return bccomp($gap, self::MIN_GAP, self::SCALE) < 0;
    }

    /**
     * Generate sequential positions for rebalancing a column.
     *
     * @param  int  $count  Number of positions to generate
     * @return array<int, string> Array of evenly-spaced positions
     */
    public static function generateSequence(int $count): array
    {
        $positions = [];
        for ($i = 1; $i <= $count; $i++) {
            $positions[] = bcmul((string) $i, self::DEFAULT_GAP, self::SCALE);
        }

        return $positions;
    }

    /**
     * Normalize a position string to ensure consistent format.
     * Converts numeric values to properly scaled decimal strings.
     */
    public static function normalize(string|int|float $position): string
    {
        return bcadd((string) $position, '0', self::SCALE);
    }

    /**
     * Compare two positions.
     *
     * @return int -1 if $a < $b, 0 if equal, 1 if $a > $b
     */
    public static function compare(string $a, string $b): int
    {
        return bccomp($a, $b, self::SCALE);
    }

    /**
     * Check if position A is less than position B.
     */
    public static function lessThan(string $a, string $b): bool
    {
        return self::compare($a, $b) < 0;
    }

    /**
     * Check if position A is greater than position B.
     */
    public static function greaterThan(string $a, string $b): bool
    {
        return self::compare($a, $b) > 0;
    }

    /**
     * Get the gap between two positions.
     */
    public static function gap(string $lower, string $upper): string
    {
        return bcsub($upper, $lower, self::SCALE);
    }

    /**
     * Generate N positions between two bounds with independent jitter.
     *
     * Useful for bulk insertions that need multiple unique positions
     * within a given range. Each position gets independent jitter.
     *
     * @param  string  $after  Lower bound position
     * @param  string  $before  Upper bound position
     * @param  int  $count  Number of positions to generate
     * @return array<int, string>  Array of unique positions
     */
    public static function generateBetween(string $after, string $before, int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $positions = [];
        $gap = bcsub($before, $after, self::SCALE);
        $step = bcdiv($gap, (string) ($count + 1), self::SCALE);

        for ($i = 1; $i <= $count; $i++) {
            $basePosition = bcadd($after, bcmul($step, (string) $i, self::SCALE), self::SCALE);

            // Add jitter to each position (5% of step size)
            $jitterRange = bcmul($step, '0.05', self::SCALE);
            $jitter = self::generateJitter($jitterRange);

            $positions[] = bcadd($basePosition, $jitter, self::SCALE);
        }

        return $positions;
    }

    /**
     * Generate cryptographically secure random jitter in range [-$maxOffset, +$maxOffset].
     *
     * Uses random_bytes() for cryptographic randomness, then scales to the
     * desired range using BCMath for precision.
     *
     * @param  string  $maxOffset  Maximum absolute offset (positive number)
     * @return string  Random value in [-maxOffset, +maxOffset]
     */
    private static function generateJitter(string $maxOffset): string
    {
        // If maxOffset is zero or very small, return zero
        if (bccomp($maxOffset, '0', self::SCALE) <= 0) {
            return '0.0000000000';
        }

        // Get 8 random bytes and convert to unsigned 64-bit integer
        $bytes = random_bytes(8);
        $randomInt = unpack('P', $bytes)[1]; // Unsigned 64-bit little-endian

        // Normalize to [0, 1] range
        // PHP_INT_MAX is the max for signed, but we have unsigned so use 2^64
        $maxUint64 = '18446744073709551615'; // 2^64 - 1
        $normalized = bcdiv((string) $randomInt, $maxUint64, self::SCALE);

        // Scale to [-1, 1] range
        $scaled = bcsub(bcmul($normalized, '2', self::SCALE), '1', self::SCALE);

        // Apply to max offset: result in [-maxOffset, +maxOffset]
        return bcmul($scaled, $maxOffset, self::SCALE);
    }
}
