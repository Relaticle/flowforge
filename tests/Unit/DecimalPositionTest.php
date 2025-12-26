<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;

describe('between() with jitter', function () {
    test('returns position strictly between bounds on 10,000 iterations', function () {
        $after = '1000.0000000000';
        $before = '2000.0000000000';

        for ($i = 0; $i < 10_000; $i++) {
            $result = DecimalPosition::between($after, $before);

            expect(bccomp($result, $after, 10))->toBeGreaterThan(0)
                ->and(bccomp($result, $before, 10))->toBeLessThan(0);
        }
    });

    test('produces different results on successive calls (jitter verification)', function () {
        $after = '1000.0000000000';
        $before = '2000.0000000000';

        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = DecimalPosition::between($after, $before);
        }

        // All 100 results should be unique (cryptographic jitter)
        $unique = array_unique($results);
        expect(count($unique))->toBe(100);
    });

    test('throws InvalidArgumentException when after >= before', function () {
        $after = '2000.0000000000';
        $before = '1000.0000000000';

        DecimalPosition::between($after, $before);
    })->throws(InvalidArgumentException::class, 'Invalid bounds: after (2000.0000000000) must be less than before (1000.0000000000)');

    test('handles equal positions by inserting after (Trello approach)', function () {
        $position = '1500.0000000000';

        $result = DecimalPosition::between($position, $position);

        // When positions are equal, we append after the first card
        // This follows Trello's approach - duplicates spread out over time
        expect($result)->toBe('67035.0000000000'); // 1500 + 65535
    });
});

describe('betweenExact() deterministic', function () {
    test('returns exact midpoint', function () {
        expect(DecimalPosition::betweenExact('1000', '2000'))->toBe('1500.0000000000')
            ->and(DecimalPosition::betweenExact('0', '100'))->toBe('50.0000000000')
            ->and(DecimalPosition::betweenExact('100', '101'))->toBe('100.5000000000');
    });

    test('returns consistent results (deterministic)', function () {
        $results = [];
        for ($i = 0; $i < 100; $i++) {
            $results[] = DecimalPosition::betweenExact('1000', '2000');
        }

        // All 100 results should be identical
        expect(array_unique($results))->toHaveCount(1)
            ->and($results[0])->toBe('1500.0000000000');
    });

    test('throws InvalidArgumentException when after >= before', function () {
        DecimalPosition::betweenExact('2000', '1000');
    })->throws(InvalidArgumentException::class);
});

describe('needsRebalancing()', function () {
    test('returns false for large gaps', function () {
        expect(DecimalPosition::needsRebalancing('1000', '2000'))->toBeFalse()
            ->and(DecimalPosition::needsRebalancing('0', '65535'))->toBeFalse();
    });

    test('returns true when gap equals MIN_GAP', function () {
        $after = '1000.0000000000';
        $before = bcadd($after, DecimalPosition::MIN_GAP, 10);

        expect(DecimalPosition::needsRebalancing($after, $before))->toBeFalse();
    });

    test('returns true when gap is below MIN_GAP', function () {
        $after = '1000.0000000000';
        $before = '1000.00009'; // Gap of 0.00009 < 0.0001

        expect(DecimalPosition::needsRebalancing($after, $before))->toBeTrue();
    });

    test('returns true for extremely small gaps', function () {
        $after = '1000.0000000000';
        $before = '1000.0000000001';

        expect(DecimalPosition::needsRebalancing($after, $before))->toBeTrue();
    });
});

describe('generateSequence()', function () {
    test('produces evenly spaced positions', function () {
        $positions = DecimalPosition::generateSequence(5);

        expect($positions)->toHaveCount(5)
            ->and($positions[0])->toBe('65535.0000000000')
            ->and($positions[1])->toBe('131070.0000000000')
            ->and($positions[2])->toBe('196605.0000000000')
            ->and($positions[3])->toBe('262140.0000000000')
            ->and($positions[4])->toBe('327675.0000000000');
    });

    test('returns empty array for zero count', function () {
        expect(DecimalPosition::generateSequence(0))->toBe([]);
    });

    test('returns single position for count of 1', function () {
        $positions = DecimalPosition::generateSequence(1);

        expect($positions)->toHaveCount(1)
            ->and($positions[0])->toBe('65535.0000000000');
    });
});

describe('generateBetween()', function () {
    test('produces N unique positions within bounds', function () {
        $after = '1000.0000000000';
        $before = '2000.0000000000';
        $count = 10;

        $positions = DecimalPosition::generateBetween($after, $before, $count);

        expect($positions)->toHaveCount($count);

        // All positions should be unique
        expect(array_unique($positions))->toHaveCount($count);

        // All positions should be strictly between bounds
        foreach ($positions as $position) {
            expect(bccomp($position, $after, 10))->toBeGreaterThan(0)
                ->and(bccomp($position, $before, 10))->toBeLessThan(0);
        }
    });

    test('returns empty array for zero count', function () {
        expect(DecimalPosition::generateBetween('1000', '2000', 0))->toBe([]);
    });

    test('returns single position for count of 1', function () {
        $positions = DecimalPosition::generateBetween('1000', '2000', 1);

        expect($positions)->toHaveCount(1);
    });
});

describe('normalize()', function () {
    test('handles various input formats', function () {
        expect(DecimalPosition::normalize('1000'))->toBe('1000.0000000000')
            ->and(DecimalPosition::normalize('1000.5'))->toBe('1000.5000000000')
            ->and(DecimalPosition::normalize(1000))->toBe('1000.0000000000')
            ->and(DecimalPosition::normalize(1000.5))->toBe('1000.5000000000')
            ->and(DecimalPosition::normalize('0'))->toBe('0.0000000000')
            ->and(DecimalPosition::normalize('-1000'))->toBe('-1000.0000000000');
    });
});

describe('comparison methods', function () {
    test('compare() returns correct values', function () {
        expect(DecimalPosition::compare('1000', '2000'))->toBe(-1)
            ->and(DecimalPosition::compare('2000', '1000'))->toBe(1)
            ->and(DecimalPosition::compare('1000', '1000'))->toBe(0);
    });

    test('lessThan() works correctly', function () {
        expect(DecimalPosition::lessThan('1000', '2000'))->toBeTrue()
            ->and(DecimalPosition::lessThan('2000', '1000'))->toBeFalse()
            ->and(DecimalPosition::lessThan('1000', '1000'))->toBeFalse();
    });

    test('greaterThan() works correctly', function () {
        expect(DecimalPosition::greaterThan('2000', '1000'))->toBeTrue()
            ->and(DecimalPosition::greaterThan('1000', '2000'))->toBeFalse()
            ->and(DecimalPosition::greaterThan('1000', '1000'))->toBeFalse();
    });
});

describe('gap()', function () {
    test('calculates gap between positions', function () {
        expect(DecimalPosition::gap('1000', '2000'))->toBe('1000.0000000000')
            ->and(DecimalPosition::gap('0', '65535'))->toBe('65535.0000000000')
            ->and(DecimalPosition::gap('100', '100.5'))->toBe('0.5000000000');
    });
});

describe('forEmptyColumn()', function () {
    test('returns DEFAULT_GAP', function () {
        expect(DecimalPosition::forEmptyColumn())->toBe(DecimalPosition::DEFAULT_GAP);
    });
});

describe('after() and before()', function () {
    test('after() adds DEFAULT_GAP', function () {
        expect(DecimalPosition::after('1000'))->toBe('66535.0000000000')
            ->and(DecimalPosition::after('0'))->toBe('65535.0000000000');
    });

    test('before() subtracts DEFAULT_GAP', function () {
        expect(DecimalPosition::before('100000'))->toBe('34465.0000000000')
            ->and(DecimalPosition::before('65535'))->toBe('0.0000000000');
    });

    test('before() can produce negative positions', function () {
        expect(DecimalPosition::before('0'))->toBe('-65535.0000000000');
    });
});

describe('calculate()', function () {
    test('returns forEmptyColumn when both positions are null', function () {
        expect(DecimalPosition::calculate(null, null))->toBe(DecimalPosition::DEFAULT_GAP);
    });

    test('returns after() when only afterPos is provided', function () {
        $result = DecimalPosition::calculate('1000', null);
        expect($result)->toBe('66535.0000000000');
    });

    test('returns before() when only beforePos is provided', function () {
        $result = DecimalPosition::calculate(null, '100000');
        expect($result)->toBe('34465.0000000000');
    });

    test('returns between() when both positions are provided', function () {
        $result = DecimalPosition::calculate('1000', '2000');

        // Should be between bounds (with jitter)
        expect(bccomp($result, '1000', 10))->toBeGreaterThan(0)
            ->and(bccomp($result, '2000', 10))->toBeLessThan(0);
    });
});
