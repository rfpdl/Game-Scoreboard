<?php

declare(strict_types=1);

use App\Domain\Rating\Services\EloCalculator;

describe('EloCalculator', function () {
    beforeEach(function () {
        $this->calculator = new EloCalculator();
    });

    describe('expectedScore', function () {
        it('returns 0.5 for equal ratings', function () {
            $expected = $this->calculator->expectedScore(1500, 1500);

            expect($expected)->toBe(0.5);
        });

        it('returns higher probability for higher rated player', function () {
            $expected = $this->calculator->expectedScore(1700, 1500);

            expect($expected)->toBeGreaterThan(0.5);
            expect($expected)->toBeLessThan(1.0);
        });

        it('returns lower probability for lower rated player', function () {
            $expected = $this->calculator->expectedScore(1300, 1500);

            expect($expected)->toBeLessThan(0.5);
            expect($expected)->toBeGreaterThan(0.0);
        });

        it('returns approximately 0.76 for 200 point advantage', function () {
            $expected = $this->calculator->expectedScore(1700, 1500);

            expect($expected)->toBeGreaterThan(0.75);
            expect($expected)->toBeLessThan(0.77);
        });
    });

    describe('calculate', function () {
        it('gives positive points for a win', function () {
            $change = $this->calculator->calculate(1500, 1500, true);

            expect($change)->toBeGreaterThan(0);
        });

        it('gives negative points for a loss', function () {
            $change = $this->calculator->calculate(1500, 1500, false);

            expect($change)->toBeLessThan(0);
        });

        it('gives +16 for equal rating win with k=32', function () {
            $change = $this->calculator->calculate(1500, 1500, true);

            expect($change)->toBe(16);
        });

        it('gives -16 for equal rating loss with k=32', function () {
            $change = $this->calculator->calculate(1500, 1500, false);

            expect($change)->toBe(-16);
        });

        it('gives more points for beating higher rated opponent', function () {
            $changeVsHigher = $this->calculator->calculate(1500, 1800, true);
            $changeVsEqual = $this->calculator->calculate(1500, 1500, true);

            expect($changeVsHigher)->toBeGreaterThan($changeVsEqual);
        });

        it('gives fewer points for beating lower rated opponent', function () {
            $changeVsLower = $this->calculator->calculate(1500, 1200, true);
            $changeVsEqual = $this->calculator->calculate(1500, 1500, true);

            expect($changeVsLower)->toBeLessThan($changeVsEqual);
        });

        it('loses more points when losing to lower rated opponent', function () {
            $lossVsLower = $this->calculator->calculate(1500, 1200, false);
            $lossVsEqual = $this->calculator->calculate(1500, 1500, false);

            expect(abs($lossVsLower))->toBeGreaterThan(abs($lossVsEqual));
        });

        it('respects custom K-factor', function () {
            $changeK32 = $this->calculator->calculate(1500, 1500, true, 32);
            $changeK40 = $this->calculator->calculate(1500, 1500, true, 40);

            expect($changeK40)->toBeGreaterThan($changeK32);
        });
    });

    describe('calculateMatch', function () {
        it('returns opposite changes for winner and loser', function () {
            $result = $this->calculator->calculateMatch(1500, 1500);

            expect($result['winner_change'])->toBe(16);
            expect($result['loser_change'])->toBe(-16);
        });

        it('handles rating difference correctly', function () {
            $result = $this->calculator->calculateMatch(1800, 1500);

            // Winner gains less (expected to win)
            expect($result['winner_change'])->toBeLessThan(16);
            // Loser loses less (expected to lose)
            expect(abs($result['loser_change']))->toBeLessThan(16);
        });

        it('allows different K-factors for each player', function () {
            $result = $this->calculator->calculateMatch(1500, 1500, 40, 24);

            expect($result['winner_change'])->toBe(20); // K=40, gain = 40 * 0.5 = 20
            expect($result['loser_change'])->toBe(-12); // K=24, loss = 24 * -0.5 = -12
        });
    });

    describe('kFactorForExperience', function () {
        it('returns 40 for new players (< 10 matches)', function () {
            expect(EloCalculator::kFactorForExperience(0))->toBe(40);
            expect(EloCalculator::kFactorForExperience(5))->toBe(40);
            expect(EloCalculator::kFactorForExperience(9))->toBe(40);
        });

        it('returns 32 for established players (10-29 matches)', function () {
            expect(EloCalculator::kFactorForExperience(10))->toBe(32);
            expect(EloCalculator::kFactorForExperience(20))->toBe(32);
            expect(EloCalculator::kFactorForExperience(29))->toBe(32);
        });

        it('returns 24 for veterans (30+ matches)', function () {
            expect(EloCalculator::kFactorForExperience(30))->toBe(24);
            expect(EloCalculator::kFactorForExperience(100))->toBe(24);
            expect(EloCalculator::kFactorForExperience(500))->toBe(24);
        });
    });

    describe('calculateNewRatings', function () {
        it('calculates new ratings for both players', function () {
            $result = $this->calculator->calculateNewRatings(1500, 1500);

            expect($result['winner_new_rating'])->toBe(1520); // New player K=40: +20
            expect($result['loser_new_rating'])->toBe(1480);  // New player K=40: -20
        });

        it('uses appropriate K-factors based on experience', function () {
            // Winner is veteran (30+ matches), loser is new
            $result = $this->calculator->calculateNewRatings(1500, 1500, 50, 5);

            expect($result['winner_change'])->toBe(12);  // K=24: 24 * 0.5 = 12
            expect($result['loser_change'])->toBe(-20); // K=40: 40 * -0.5 = -20
        });

        it('handles upset correctly (lower rated beats higher rated)', function () {
            // Lower rated player (1300) beats higher rated (1700)
            $result = $this->calculator->calculateNewRatings(1300, 1700);

            // Winner gains significantly
            expect($result['winner_change'])->toBeGreaterThan(25);
            // Loser loses significantly
            expect(abs($result['loser_change']))->toBeGreaterThan(25);
        });
    });

    describe('defaultRating', function () {
        it('returns 1000', function () {
            expect(EloCalculator::defaultRating())->toBe(1000);
        });
    });
});
