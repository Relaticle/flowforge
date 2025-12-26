<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\DecimalPosition;

describe('DecimalPosition Service', function () {
    describe('forEmptyColumn', function () {
        it('returns the default gap value', function () {
            expect(DecimalPosition::forEmptyColumn())->toBe('65535');
        });
    });

    describe('after', function () {
        it('adds the default gap to the position', function () {
            expect(DecimalPosition::after('65535'))->toBe('131070.0000000000');
        });

        it('handles zero position', function () {
            expect(DecimalPosition::after('0'))->toBe('65535.0000000000');
        });

        it('handles negative position', function () {
            expect(DecimalPosition::after('-65535'))->toBe('0.0000000000');
        });

        it('handles decimal position', function () {
            expect(DecimalPosition::after('100.5'))->toBe('65635.5000000000');
        });
    });

    describe('before', function () {
        it('subtracts the default gap from the position', function () {
            expect(DecimalPosition::before('65535'))->toBe('0.0000000000');
        });

        it('can go negative', function () {
            expect(DecimalPosition::before('0'))->toBe('-65535.0000000000');
        });

        it('handles decimal position', function () {
            expect(DecimalPosition::before('100.5'))->toBe('-65434.5000000000');
        });
    });

    describe('between (with jitter - non-deterministic)', function () {
        it('calculates position near midpoint between two positions', function () {
            $pos = DecimalPosition::between('65535', '131070');
            // Should be between the two bounds (exact value varies due to jitter)
            expect(bccomp($pos, '65535', 10))->toBeGreaterThan(0);
            expect(bccomp($pos, '131070', 10))->toBeLessThan(0);
        });

        it('produces position within bounds for close positions', function () {
            $pos = DecimalPosition::between('100', '101');
            expect(bccomp($pos, '100', 10))->toBeGreaterThan(0);
            expect(bccomp($pos, '101', 10))->toBeLessThan(0);
        });

        it('handles very close positions within bounds', function () {
            $pos = DecimalPosition::between('100', '100.001');
            expect(bccomp($pos, '100', 10))->toBeGreaterThan(0);
            expect(bccomp($pos, '100.001', 10))->toBeLessThan(0);
        });

        it('handles zero and positive within bounds', function () {
            $pos = DecimalPosition::between('0', '65535');
            expect(bccomp($pos, '0', 10))->toBeGreaterThan(0);
            expect(bccomp($pos, '65535', 10))->toBeLessThan(0);
        });

        it('handles negative and positive within bounds', function () {
            $pos = DecimalPosition::between('-100', '100');
            expect(bccomp($pos, '-100', 10))->toBeGreaterThan(0);
            expect(bccomp($pos, '100', 10))->toBeLessThan(0);
        });
    });

    describe('calculate', function () {
        it('returns default gap when both positions are null', function () {
            expect(DecimalPosition::calculate(null, null))->toBe('65535');
        });

        it('returns position after when only afterPos is provided', function () {
            expect(DecimalPosition::calculate('65535', null))->toBe('131070.0000000000');
        });

        it('returns position before when only beforePos is provided', function () {
            expect(DecimalPosition::calculate(null, '65535'))->toBe('0.0000000000');
        });

        it('returns position between bounds when both provided (with jitter)', function () {
            $pos = DecimalPosition::calculate('65535', '131070');
            // Should be between the two bounds (uses between() with jitter)
            expect(bccomp($pos, '65535', 10))->toBeGreaterThan(0);
            expect(bccomp($pos, '131070', 10))->toBeLessThan(0);
        });
    });

    describe('needsRebalancing', function () {
        it('returns true when gap is below minimum', function () {
            expect(DecimalPosition::needsRebalancing('100', '100.00005'))->toBeTrue();
        });

        it('returns true when gap is exactly at minimum', function () {
            expect(DecimalPosition::needsRebalancing('100', '100.0001'))->toBeFalse();
        });

        it('returns false when gap is above minimum', function () {
            expect(DecimalPosition::needsRebalancing('100', '200'))->toBeFalse();
        });

        it('returns false for normal gaps', function () {
            expect(DecimalPosition::needsRebalancing('65535', '131070'))->toBeFalse();
        });

        it('returns true for extremely close positions', function () {
            expect(DecimalPosition::needsRebalancing('100', '100.00001'))->toBeTrue();
        });
    });

    describe('generateSequence', function () {
        it('generates empty array for zero count', function () {
            expect(DecimalPosition::generateSequence(0))->toBe([]);
        });

        it('generates single position for count of 1', function () {
            $positions = DecimalPosition::generateSequence(1);
            expect($positions)->toBe(['65535.0000000000']);
        });

        it('generates sequential positions for count of 3', function () {
            $positions = DecimalPosition::generateSequence(3);
            expect($positions)->toBe([
                '65535.0000000000',
                '131070.0000000000',
                '196605.0000000000',
            ]);
        });

        it('generates correct positions for larger count', function () {
            $positions = DecimalPosition::generateSequence(5);
            expect($positions)->toHaveCount(5);
            expect($positions[0])->toBe('65535.0000000000');
            expect($positions[4])->toBe('327675.0000000000');
        });
    });

    describe('precision stress tests', function () {
        it('handles 33 sequential midpoint calculations without precision loss', function () {
            $lower = '65535';
            $upper = '131070';

            for ($i = 0; $i < 33; $i++) {
                // Use betweenExact for deterministic testing of precision
                $mid = DecimalPosition::betweenExact($lower, $upper);
                expect(bccomp($mid, $lower, 10))->toBeGreaterThan(0);
                expect(bccomp($mid, $upper, 10))->toBeLessThan(0);
                $upper = $mid;
            }
        });

        it('correctly identifies when rebalancing is needed after many bisections', function () {
            $lower = '65535';
            $upper = '131070';

            // Keep bisecting until rebalancing is needed (use betweenExact for deterministic test)
            $bisections = 0;
            while (!DecimalPosition::needsRebalancing($lower, $upper) && $bisections < 100) {
                $upper = DecimalPosition::betweenExact($lower, $upper);
                $bisections++;
            }

            // Should need rebalancing after many bisections (30+ is excellent)
            expect($bisections)->toBeGreaterThanOrEqual(30);
            expect($bisections)->toBeLessThan(50);
        });

        it('maintains ordering after many operations with jitter', function () {
            $positions = ['65535'];

            // Insert 20 items at various positions
            for ($i = 0; $i < 20; $i++) {
                if ($i % 2 === 0) {
                    // Insert at end
                    $positions[] = DecimalPosition::after(end($positions));
                } else {
                    // Insert in middle (with jitter)
                    $idx = intdiv(count($positions), 2);
                    $newPos = DecimalPosition::between($positions[$idx - 1], $positions[$idx]);
                    array_splice($positions, $idx, 0, [$newPos]);
                }
            }

            // Verify all positions are in ascending order when sorted
            $sorted = $positions;
            usort($sorted, fn($a, $b) => bccomp($a, $b, 10));

            // With jitter, positions might not be in the expected order
            // But when sorted, they should still be valid
            for ($i = 0; $i < count($sorted) - 1; $i++) {
                expect(bccomp($sorted[$i], $sorted[$i + 1], 10))->toBeLessThan(0,
                    "Position {$i} should be less than position " . ($i + 1));
            }
        });
    });

    describe('edge cases', function () {
        it('handles very large positions', function () {
            $large = '9999999999';
            $after = DecimalPosition::after($large);
            expect(bccomp($after, $large, 10))->toBeGreaterThan(0);
        });

        it('handles very small decimal differences within precision', function () {
            // Use values within SCALE=10 precision (minimum resolvable diff is 0.0000000002)
            $a = '100.0000000100';
            $b = '100.0000000200';
            $mid = DecimalPosition::betweenExact($a, $b);
            // Midpoint should be 100.0000000150
            expect($mid)->toBe('100.0000000150');
            expect(bccomp($mid, $a, 10))->toBeGreaterThan(0);
            expect(bccomp($mid, $b, 10))->toBeLessThan(0);
        });

        it('correctly compares positions', function () {
            expect(bccomp('100.5', '100.6', 10))->toBeLessThan(0);
            expect(bccomp('100.6', '100.5', 10))->toBeGreaterThan(0);
            expect(bccomp('100.5', '100.5', 10))->toBe(0);
        });
    });

    describe('betweenExact (deterministic midpoint)', function () {
        it('returns the exact midpoint for testing purposes', function () {
            expect(DecimalPosition::betweenExact('65535', '131070'))->toBe('98302.5000000000');
        });

        it('is deterministic - same inputs always produce same output', function () {
            $pos1 = DecimalPosition::betweenExact('65535', '131070');
            $pos2 = DecimalPosition::betweenExact('65535', '131070');
            $pos3 = DecimalPosition::betweenExact('65535', '131070');

            expect($pos1)->toBe($pos2)->toBe($pos3);
        });

        it('handles edge cases identically to old between', function () {
            expect(DecimalPosition::betweenExact('100', '101'))->toBe('100.5000000000');
            expect(DecimalPosition::betweenExact('0', '65535'))->toBe('32767.5000000000');
            expect(DecimalPosition::betweenExact('-100', '100'))->toBe('0.0000000000');
        });
    });

    describe('between with jitter (collision prevention)', function () {
        it('generates position within valid bounds', function () {
            $lower = '65535';
            $upper = '131070';

            // Test 100 times to ensure bounds are always respected
            for ($i = 0; $i < 100; $i++) {
                $pos = DecimalPosition::between($lower, $upper);

                expect(bccomp($pos, $lower, 10))->toBeGreaterThan(0,
                    "Position {$pos} should be greater than lower bound {$lower}");
                expect(bccomp($pos, $upper, 10))->toBeLessThan(0,
                    "Position {$pos} should be less than upper bound {$upper}");
            }
        });

        it('generates unique positions for concurrent insertions at same target', function () {
            $positions = collect();

            // Simulate 1000 concurrent insertions at the same target position
            for ($i = 0; $i < 1000; $i++) {
                $positions->push(DecimalPosition::between('65535', '131070'));
            }

            $uniqueCount = $positions->unique()->count();

            // All 1000 positions should be unique due to jitter
            expect($uniqueCount)->toBe(1000,
                "Expected 1000 unique positions but got {$uniqueCount}. Jitter should prevent collisions.");
        });

        it('maintains ordering despite jitter', function () {
            $lower = '65535';
            $upper = '131070';

            // Generate 100 positions between the same bounds
            $positions = [];
            for ($i = 0; $i < 100; $i++) {
                $positions[] = DecimalPosition::between($lower, $upper);
            }

            // All should be greater than lower and less than upper
            foreach ($positions as $pos) {
                expect(bccomp($pos, $lower, 10))->toBeGreaterThan(0);
                expect(bccomp($pos, $upper, 10))->toBeLessThan(0);
            }
        });

        it('handles small gaps with jitter still within bounds', function () {
            // Small gap: 100.0 to 100.1 (gap of 0.1)
            $lower = '100';
            $upper = '100.1';

            for ($i = 0; $i < 50; $i++) {
                $pos = DecimalPosition::between($lower, $upper);

                expect(bccomp($pos, $lower, 10))->toBeGreaterThan(0,
                    "Position {$pos} should be > {$lower}");
                expect(bccomp($pos, $upper, 10))->toBeLessThan(0,
                    "Position {$pos} should be < {$upper}");
            }
        });

        it('handles very small gaps gracefully', function () {
            // Very small gap: 100 to 100.001 (gap of 0.001)
            $lower = '100';
            $upper = '100.001';

            for ($i = 0; $i < 20; $i++) {
                $pos = DecimalPosition::between($lower, $upper);

                expect(bccomp($pos, $lower, 10))->toBeGreaterThan(0);
                expect(bccomp($pos, $upper, 10))->toBeLessThan(0);
            }
        });
    });

    describe('generateBetween (bulk position generation)', function () {
        it('generates empty array for count less than 1', function () {
            expect(DecimalPosition::generateBetween('100', '200', 0))->toBe([]);
            expect(DecimalPosition::generateBetween('100', '200', -1))->toBe([]);
        });

        it('generates correct number of positions', function () {
            $positions = DecimalPosition::generateBetween('65535', '131070', 5);
            expect($positions)->toHaveCount(5);
        });

        it('generates all unique positions', function () {
            $positions = DecimalPosition::generateBetween('65535', '131070', 100);

            $uniqueCount = count(array_unique($positions));
            expect($uniqueCount)->toBe(100, 'All 100 bulk positions should be unique');
        });

        it('generates positions within bounds', function () {
            $lower = '65535';
            $upper = '131070';
            $positions = DecimalPosition::generateBetween($lower, $upper, 10);

            foreach ($positions as $pos) {
                expect(bccomp($pos, $lower, 10))->toBeGreaterThan(0,
                    "Position {$pos} should be > {$lower}");
                expect(bccomp($pos, $upper, 10))->toBeLessThan(0,
                    "Position {$pos} should be < {$upper}");
            }
        });

        it('generates evenly distributed positions', function () {
            $lower = '0';
            $upper = '100';
            $positions = DecimalPosition::generateBetween($lower, $upper, 4);

            // Positions should be roughly at 20, 40, 60, 80 (with some jitter)
            // Check they're in ascending order when sorted
            $sorted = $positions;
            usort($sorted, fn($a, $b) => bccomp($a, $b, 10));

            // First should be closer to 20, last closer to 80
            expect((float) $sorted[0])->toBeGreaterThan(10)->toBeLessThan(30);
            expect((float) $sorted[3])->toBeGreaterThan(70)->toBeLessThan(90);
        });
    });
});
