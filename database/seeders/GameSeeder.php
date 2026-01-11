<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

final class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = [
            [
                'name' => 'Pool',
                'slug' => 'pool',
                'description' => 'Classic 8-ball pool',
                'rules' => [
                    'Players take turns shooting',
                    'One player pockets solids (1-7), other pockets stripes (9-15)',
                    'Pocket all your balls, then sink the 8-ball to win',
                    'Sinking the 8-ball early or scratching on it loses the game',
                    'Call your pocket for the 8-ball shot',
                ],
                'icon' => '🎱',
                'min_players' => 2,
                'max_players' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Backgammon',
                'slug' => 'backgammon',
                'description' => 'Classic backgammon board game',
                'rules' => [
                    'Roll dice and move checkers toward your home board',
                    'Move checkers the exact number shown on each die',
                    "Land on opponent's single checker to send it to the bar",
                    'Bear off all 15 checkers from your home board to win',
                    'Doubling cube can be used to raise stakes',
                ],
                'icon' => '🎲',
                'min_players' => 2,
                'max_players' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Darts',
                'slug' => 'darts',
                'description' => '501 darts game',
                'rules' => [
                    'Start at 501 points and count down to zero',
                    'Each turn consists of throwing 3 darts',
                    'Must finish by hitting a double (outer ring)',
                    'Going below zero or hitting exactly 1 busts your turn',
                    'First to reach exactly zero wins',
                ],
                'icon' => '🎯',
                'min_players' => 2,
                'max_players' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Foosball',
                'slug' => 'foosball',
                'description' => 'Table football',
                'rules' => [
                    "Score by getting the ball into the opponent's goal",
                    'No spinning rods (360° rotation before/after hitting)',
                    'First to 5 or 10 goals wins (agree before playing)',
                    'Ball out of play: serve from nearest rod',
                    'Each goal counts as 1 point regardless of how scored',
                ],
                'icon' => '⚽',
                'min_players' => 2,
                'max_players' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($games as $game) {
            Game::updateOrCreate(
                ['slug' => $game['slug']],
                $game
            );
        }
    }
}
