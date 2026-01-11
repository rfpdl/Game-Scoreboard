<?php

declare(strict_types=1);

namespace App\Domain\Rating\Services;

final readonly class EloCalculator
{
    private const DEFAULT_K_FACTOR = 32;

    private const DEFAULT_RATING = 1000;

    public function __construct(
        private int $kFactor = self::DEFAULT_K_FACTOR
    ) {}

    /**
     * Get the appropriate K-factor based on matches played.
     * New players have higher K-factor for faster calibration.
     *
     * @param  int  $matchesPlayed  Number of matches the player has played
     * @return int The K-factor to use
     */
    public static function kFactorForExperience(int $matchesPlayed): int
    {
        if ($matchesPlayed < 10) {
            return 40; // New players - faster calibration
        }

        if ($matchesPlayed < 30) {
            return 32; // Established players
        }

        return 24; // Veterans - more stable
    }

    /**
     * Get the default starting rating.
     */
    public static function defaultRating(): int
    {
        return self::DEFAULT_RATING;
    }

    /**
     * Calculate the rating change for a player after a match.
     *
     * @param  int  $playerRating  The player's current rating
     * @param  int  $opponentRating  The opponent's current rating
     * @param  bool  $won  Whether the player won
     * @param  int|null  $kFactor  Optional K-factor override
     * @return int The rating change (positive for gain, negative for loss)
     */
    public function calculate(
        int $playerRating,
        int $opponentRating,
        bool $won,
        ?int $kFactor = null
    ): int {
        $k = $kFactor ?? $this->kFactor;
        $expected = $this->expectedScore($playerRating, $opponentRating);
        $actual = $won ? 1.0 : 0.0;

        return (int) round($k * ($actual - $expected));
    }

    /**
     * Calculate rating changes for both players in a match.
     *
     * @param  int  $winnerRating  The winner's current rating
     * @param  int  $loserRating  The loser's current rating
     * @param  int|null  $winnerKFactor  Optional K-factor for winner
     * @param  int|null  $loserKFactor  Optional K-factor for loser
     * @param  int  $loserWinStreak  Loser's current win streak (for streak bonus)
     * @param  bool  $streakMultiplierEnabled  Whether streak multiplier is enabled
     * @return array{winner_change: int, loser_change: int}
     */
    public function calculateMatch(
        int $winnerRating,
        int $loserRating,
        ?int $winnerKFactor = null,
        ?int $loserKFactor = null,
        int $loserWinStreak = 0,
        bool $streakMultiplierEnabled = false
    ): array {
        $winnerChange = $this->calculate($winnerRating, $loserRating, true, $winnerKFactor);
        $loserChange = $this->calculate($loserRating, $winnerRating, false, $loserKFactor);

        // Apply streak bonus if enabled and loser had a significant streak
        if ($streakMultiplierEnabled && $loserWinStreak >= 3) {
            $streakBonus = $this->calculateStreakBonus($loserWinStreak);
            $winnerChange += $streakBonus;
            // Loser loses the same bonus amount
            $loserChange -= $streakBonus;
        }

        return [
            'winner_change' => $winnerChange,
            'loser_change' => $loserChange,
        ];
    }

    /**
     * Calculate the bonus points for breaking a win streak.
     * Streak of 3: +3 bonus
     * Streak of 5: +5 bonus
     * Streak of 10+: +10 bonus (capped)
     */
    public function calculateStreakBonus(int $streak): int
    {
        if ($streak < 3) {
            return 0;
        }

        // Cap bonus at 10 points
        return min($streak, 10);
    }

    /**
     * Calculate the expected score for a player against an opponent.
     * This is the probability of winning based on rating difference.
     *
     * @param  int  $playerRating  The player's current rating
     * @param  int  $opponentRating  The opponent's current rating
     * @return float Expected score between 0 and 1
     */
    public function expectedScore(int $playerRating, int $opponentRating): float
    {
        return 1.0 / (1.0 + 10 ** (($opponentRating - $playerRating) / 400.0));
    }

    /**
     * Calculate new ratings for both players after a match.
     *
     * @param  int  $winnerRating  Current rating of winner
     * @param  int  $loserRating  Current rating of loser
     * @param  int  $winnerMatchesPlayed  Matches played by winner
     * @param  int  $loserMatchesPlayed  Matches played by loser
     * @return array{winner_new_rating: int, winner_change: int, loser_new_rating: int, loser_change: int}
     */
    public function calculateNewRatings(
        int $winnerRating,
        int $loserRating,
        int $winnerMatchesPlayed = 0,
        int $loserMatchesPlayed = 0
    ): array {
        $winnerKFactor = self::kFactorForExperience($winnerMatchesPlayed);
        $loserKFactor = self::kFactorForExperience($loserMatchesPlayed);

        $changes = $this->calculateMatch(
            $winnerRating,
            $loserRating,
            $winnerKFactor,
            $loserKFactor
        );

        return [
            'winner_new_rating' => $winnerRating + $changes['winner_change'],
            'winner_change' => $changes['winner_change'],
            'loser_new_rating' => $loserRating + $changes['loser_change'],
            'loser_change' => $changes['loser_change'],
        ];
    }
}
