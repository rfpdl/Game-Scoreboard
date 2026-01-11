<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MatchPlayer>
 */
final class MatchPlayerFactory extends Factory
{
    protected $model = MatchPlayer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'match_id' => GameMatch::factory(),
            'user_id' => User::factory(),
            'team' => 'team_a',
            'result' => 'pending',
            'placement' => null,
            'rating_before' => null,
            'rating_after' => null,
            'rating_change' => null,
            'confirmed_at' => null,
        ];
    }

    /**
     * Indicate the player is on team B.
     */
    public function teamB(): static
    {
        return $this->state(fn (array $attributes): array => [
            'team' => 'team_b',
        ]);
    }

    /**
     * Indicate the player has confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate the player won.
     */
    public function winner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'result' => 'win',
        ]);
    }

    /**
     * Indicate the player lost.
     */
    public function loser(): static
    {
        return $this->state(fn (array $attributes): array => [
            'result' => 'lose',
        ]);
    }

    /**
     * Set rating information.
     */
    public function withRating(int $before, int $after): static
    {
        return $this->state(fn (array $attributes): array => [
            'rating_before' => $before,
            'rating_after' => $after,
            'rating_change' => $after - $before,
        ]);
    }
}
