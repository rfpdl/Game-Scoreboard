<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\PlayerRating;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlayerRating>
 */
final class PlayerRatingFactory extends Factory
{
    protected $model = PlayerRating::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
            'rating' => 1500,
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'win_streak' => 0,
            'best_rating' => 1500,
        ];
    }

    /**
     * Set specific rating.
     */
    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes): array => [
            'rating' => $rating,
        ]);
    }

    /**
     * Set wins and losses.
     */
    public function withRecord(int $wins, int $losses): static
    {
        return $this->state(fn (array $attributes): array => [
            'wins' => $wins,
            'losses' => $losses,
        ]);
    }
}
