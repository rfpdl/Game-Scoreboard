<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Game;
use Spatie\LaravelData\Data;

final class GameData extends Data
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?array $rules,
        public readonly ?string $icon,
        public readonly int $minPlayers,
        public readonly int $maxPlayers,
        public readonly bool $isActive,
    ) {}

    public static function fromModel(Game $game): self
    {
        return new self(
            id: $game->id,
            name: $game->name,
            slug: $game->slug,
            description: $game->description,
            rules: $game->rules,
            icon: $game->icon,
            minPlayers: $game->min_players,
            maxPlayers: $game->max_players,
            isActive: $game->is_active,
        );
    }
}
