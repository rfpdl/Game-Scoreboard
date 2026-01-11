<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameMatch>
 */
final class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'game_id' => Game::factory(),
            'match_code' => mb_strtoupper(Str::random(6)),
            'match_format' => '1v1',
            'max_players' => 2,
            'match_type' => 'quick',
            'name' => null,
            'scheduled_at' => null,
            'share_token' => Str::random(32),
            'status' => 'pending',
            'created_by_user_id' => User::factory(),
            'played_at' => null,
        ];
    }

    /**
     * Indicate the match is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'played_at' => now(),
        ]);
    }

    /**
     * Indicate the match is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate the match is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Set the match format to 2v2.
     */
    public function twoVsTwo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'match_format' => '2v2',
            'max_players' => 4,
        ]);
    }
}
