<?php

declare(strict_types=1);

use Relaticle\Flowforge\Services\Rank;

/**
 * Real-world battle tests for Rank service.
 * Tests card movement scenarios like Kanban boards, task lists, etc.
 */
describe('Rank Service Real-World Scenarios', function () {
    it('creates initial rank for empty sequence', function () {
        $rank = Rank::forEmptySequence();

        expect($rank->get())->toBeString()
            ->and(strlen($rank->get()))->toBeGreaterThan(0);
    });

    it('handles single card scenario', function () {
        $cardA = Rank::forEmptySequence();

        expect($cardA->get())->toBeString();
    });

    describe('Two Cards Scenario (A, B)', function () {
        beforeEach(function () {
            $this->cardA = Rank::forEmptySequence();
            $this->cardB = Rank::after($this->cardA);
        });

        it('creates two cards in sequence', function () {
            expect($this->cardA->get())->toBeLessThan($this->cardB->get());
        });

        it('moves A after B (A->B)', function () {
            $newA = Rank::after($this->cardB);

            expect($this->cardB->get())->toBeLessThan($newA->get());
        });

        it('moves B before A (B->A)', function () {
            $newB = Rank::before($this->cardA);

            expect($newB->get())->toBeLessThan($this->cardA->get());
        });
    });

    describe('Three Cards Scenario (A, B, C)', function () {
        beforeEach(function () {
            $this->cardA = Rank::forEmptySequence();
            $this->cardB = Rank::after($this->cardA);
            $this->cardC = Rank::after($this->cardB);
        });

        it('creates three cards in sequence', function () {
            expect($this->cardA->get())->toBeLessThan($this->cardB->get())
                ->and($this->cardB->get())->toBeLessThan($this->cardC->get());
        });

        it('moves A between B and C', function () {
            $newA = Rank::betweenRanks($this->cardB, $this->cardC);

            expect($this->cardB->get())->toBeLessThan($newA->get())
                ->and($newA->get())->toBeLessThan($this->cardC->get());
        });

        it('moves C between A and B', function () {
            $newC = Rank::betweenRanks($this->cardA, $this->cardB);

            expect($this->cardA->get())->toBeLessThan($newC->get())
                ->and($newC->get())->toBeLessThan($this->cardB->get());
        });

        it('moves B to the end', function () {
            $newB = Rank::after($this->cardC);

            expect($this->cardC->get())->toBeLessThan($newB->get());
        });

        it('moves B to the beginning', function () {
            $newB = Rank::before($this->cardA);

            expect($newB->get())->toBeLessThan($this->cardA->get());
        });
    });

    describe('Four Cards Scenario (A, B, C, D)', function () {
        beforeEach(function () {
            $this->cardA = Rank::forEmptySequence();
            $this->cardB = Rank::after($this->cardA);
            $this->cardC = Rank::after($this->cardB);
            $this->cardD = Rank::after($this->cardC);

            $this->originalOrder = [
                'A' => $this->cardA->get(),
                'B' => $this->cardB->get(),
                'C' => $this->cardC->get(),
                'D' => $this->cardD->get(),
            ];
        });

        it('creates four cards in sequence', function () {
            expect($this->cardA->get())->toBeLessThan($this->cardB->get())
                ->and($this->cardB->get())->toBeLessThan($this->cardC->get())
                ->and($this->cardC->get())->toBeLessThan($this->cardD->get());
        });

        it('moves A after B (A->B)', function () {
            $newA = Rank::betweenRanks($this->cardB, $this->cardC);

            expect($this->cardB->get())->toBeLessThan($newA->get())
                ->and($newA->get())->toBeLessThan($this->cardC->get());
        });

        it('moves B after A (B->A)', function () {
            $newB = Rank::before($this->cardA);

            expect($newB->get())->toBeLessThan($this->cardA->get());
        });

        it('moves A after B then B after A (multiple swaps)', function () {
            // A->B
            $newA = Rank::betweenRanks($this->cardB, $this->cardC);
            expect($this->cardB->get())->toBeLessThan($newA->get())
                ->and($newA->get())->toBeLessThan($this->cardC->get());

            // B->A (using original A position)
            $newB = Rank::before($this->cardA);
            expect($newB->get())->toBeLessThan($this->cardA->get());
        });

        it('moves A to end', function () {
            $newA = Rank::after($this->cardD);

            expect($this->cardD->get())->toBeLessThan($newA->get());
        });

        it('moves D to beginning', function () {
            $newD = Rank::before($this->cardA);

            expect($newD->get())->toBeLessThan($this->cardA->get());
        });

        it('moves B between C and D', function () {
            $newB = Rank::betweenRanks($this->cardC, $this->cardD);

            expect($this->cardC->get())->toBeLessThan($newB->get())
                ->and($newB->get())->toBeLessThan($this->cardD->get());
        });

        it('moves C between A and B', function () {
            $newC = Rank::betweenRanks($this->cardA, $this->cardB);

            expect($this->cardA->get())->toBeLessThan($newC->get())
                ->and($newC->get())->toBeLessThan($this->cardB->get());
        });

        describe('Complex Movement Sequences', function () {
            it('performs multiple random movements', function () {
                $cards = [
                    'A' => $this->cardA,
                    'B' => $this->cardB,
                    'C' => $this->cardC,
                    'D' => $this->cardD,
                ];

                // Move A after C
                $cards['A'] = Rank::betweenRanks($this->cardC, $this->cardD);

                // Move B to beginning
                $cards['B'] = Rank::before($this->cardA);

                // Move D between original A and new A positions
                $cards['D'] = Rank::betweenRanks($this->cardA, $cards['A']);

                // All cards should be unique
                $ranks = array_map(fn ($card) => $card->get(), $cards);
                expect(array_unique($ranks))->toHaveCount(4);

                // Check that ordering is valid for each moved card
                expect($cards['B']->get())->toBeLessThan($this->cardA->get());
                expect($this->cardC->get())->toBeLessThan($cards['A']->get())
                    ->and($cards['A']->get())->toBeLessThan($this->cardD->get());
                expect($this->cardA->get())->toBeLessThan($cards['D']->get())
                    ->and($cards['D']->get())->toBeLessThan($cards['A']->get());
            });
        });
    });

    describe('Stress Testing', function () {
        it('handles 100 sequential insertions', function () {
            $cards = [Rank::forEmptySequence()];

            for ($i = 1; $i < 100; $i++) {
                $cards[] = Rank::after(end($cards));
            }

            // Verify all are unique and in order
            $ranks = array_map(fn ($card) => $card->get(), $cards);
            expect(array_unique($ranks))->toHaveCount(100);

            $sortedRanks = $ranks;
            sort($sortedRanks);
            expect($ranks)->toBe($sortedRanks);
        });

        it('handles many insertions with cascading positions', function () {
            $first = Rank::forEmptySequence();
            $last = Rank::after($first);
            $insertions = [$first];

            // Create a cascading series of insertions
            for ($i = 0; $i < 10; $i++) {
                $newRank = Rank::betweenRanks($insertions[count($insertions) - 1], $last);
                $insertions[] = $newRank;
            }

            // Verify all are unique and properly ordered
            $ranks = array_map(fn ($card) => $card->get(), $insertions);
            expect(array_unique($ranks))->toHaveCount(11);

            // Check ordering
            for ($i = 0; $i < count($ranks) - 1; $i++) {
                expect($ranks[$i])->toBeLessThan($ranks[$i + 1]);
            }
        });
    });

    describe('Kanban Board Simulation', function () {
        beforeEach(function () {
            // Simulate 3 columns with tasks
            $this->todoColumn = [
                Rank::forEmptySequence(),
                Rank::after(Rank::forEmptySequence()),
            ];

            $this->inProgressColumn = [
                Rank::fromString('m1'),
                Rank::fromString('m2'),
            ];

            $this->doneColumn = [
                Rank::fromString('x1'),
                Rank::fromString('x2'),
            ];
        });

        it('moves task from todo to in-progress', function () {
            $taskToMove = $this->todoColumn[0];

            // Move to end of in-progress column
            $newRank = Rank::after(end($this->inProgressColumn));

            expect(end($this->inProgressColumn)->get())->toBeLessThan($newRank->get());
        });

        it('moves task to beginning of column', function () {
            $taskToMove = $this->doneColumn[1];

            // Move to beginning of todo column
            $newRank = Rank::before($this->todoColumn[0]);

            expect($newRank->get())->toBeLessThan($this->todoColumn[0]->get());
        });

        it('reorders within same column', function () {
            // Move second task in todo to first position
            $newRank = Rank::before($this->todoColumn[0]);

            expect($newRank->get())->toBeLessThan($this->todoColumn[0]->get());
        });
    });

    describe('Edge Cases and Error Handling', function () {
        it('throws exception when prev >= next in betweenRanks', function () {
            $first = Rank::forEmptySequence();
            $second = Rank::after($first);

            expect(fn () => Rank::betweenRanks($second, $first))
                ->toThrow(Relaticle\Flowforge\Exceptions\PrevGreaterThanOrEquals::class);
        });

        it('throws exception for invalid characters', function () {
            expect(fn () => Rank::fromString('invalid!'))
                ->toThrow(Relaticle\Flowforge\Exceptions\InvalidChars::class);
        });

        it('throws exception for rank ending with MIN_CHAR', function () {
            expect(fn () => Rank::fromString('a0'))
                ->toThrow(Relaticle\Flowforge\Exceptions\LastCharCantBeEqualToMinChar::class);
        });
    });

    describe('Real-world Performance Characteristics', function () {
        it('maintains reasonable rank lengths under normal usage', function () {
            $cards = [Rank::forEmptySequence()];

            // Simulate typical usage - adding cards and occasional reordering
            for ($i = 0; $i < 20; $i++) {
                $cards[] = Rank::after(end($cards));

                // Occasionally insert between existing cards (keep array sorted)
                if ($i % 5 === 0 && count($cards) > 2) {
                    // Sort cards to ensure proper ordering before insertion
                    usort($cards, fn ($a, $b) => strcmp($a->get(), $b->get()));
                    $randomIndex = random_int(0, count($cards) - 2);
                    $newCard = Rank::betweenRanks($cards[$randomIndex], $cards[$randomIndex + 1]);
                    $cards[] = $newCard;
                }
            }

            // Most ranks should be reasonable length
            $longRanks = array_filter($cards, fn ($card) => strlen($card->get()) > 10);
            expect($longRanks)->toHaveCount(0, 'Ranks should stay reasonable length under normal usage');
        });

        it('generates lexicographically ordered ranks', function () {
            $first = Rank::forEmptySequence();
            $second = Rank::after($first);
            $between = Rank::betweenRanks($first, $second);

            // Test PHP string comparison matches our ordering
            expect(strcmp($first->get(), $between->get()))->toBeLessThan(0)
                ->and(strcmp($between->get(), $second->get()))->toBeLessThan(0);
        });
    });
});
