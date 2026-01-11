<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\GameMatch;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class MatchData extends Data
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $gameId,
        public readonly string $matchCode,
        public readonly string $matchType,
        public readonly string $matchFormat,
        public readonly int $maxPlayers,
        public readonly ?string $name,
        public readonly ?CarbonInterface $scheduledAt,
        public readonly ?string $shareUrl,
        public readonly string $status,
        public readonly int $createdByUserId,
        public readonly ?CarbonInterface $playedAt,
        public readonly ?string $gameName = null,
        public readonly ?string $gameIcon = null,
        public readonly ?array $gameRules = null,
        public readonly ?string $createdByName = null,
        /** @var MatchPlayerData[] */
        public readonly array $players = [],
    ) {}

    public static function fromModel(GameMatch $match): self
    {
        return new self(
            id: $match->id,
            uuid: $match->uuid,
            gameId: $match->game_id,
            matchCode: $match->match_code,
            matchType: $match->match_type ?? 'quick',
            matchFormat: $match->match_format ?? '1v1',
            maxPlayers: $match->max_players ?? 2,
            name: $match->name,
            scheduledAt: $match->scheduled_at,
            shareUrl: $match->share_token ? $match->getShareUrl() : null,
            status: $match->status,
            createdByUserId: $match->created_by_user_id,
            playedAt: $match->played_at,
            gameName: $match->game?->name,
            gameIcon: $match->game?->icon,
            gameRules: $match->game?->rules,
            createdByName: $match->createdBy?->name,
            players: $match->players->map(fn (\App\Models\MatchPlayer $p): MatchPlayerData => MatchPlayerData::fromModel($p))->all(),
        );
    }
}
