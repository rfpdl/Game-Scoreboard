<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\MatchPlayer;
use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class MatchPlayerData extends Data
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $matchId,
        public readonly int $userId,
        public readonly ?string $team,
        public readonly string $result,
        public readonly ?int $placement,
        public readonly ?int $ratingBefore,
        public readonly ?int $ratingAfter,
        public readonly ?int $ratingChange,
        public readonly ?CarbonInterface $confirmedAt,
        public readonly ?string $userName = null,
        public readonly ?string $userAvatar = null,
        public readonly ?string $userNickname = null,
    ) {}

    public static function fromModel(MatchPlayer $player): self
    {
        $avatar = $player->user?->avatar;

        return new self(
            id: $player->id,
            matchId: $player->match_id,
            userId: $player->user_id,
            team: $player->team,
            result: $player->result,
            placement: $player->placement,
            ratingBefore: $player->rating_before,
            ratingAfter: $player->rating_after,
            ratingChange: $player->rating_change,
            confirmedAt: $player->confirmed_at,
            userName: $player->user?->display_name,
            userAvatar: $avatar ? '/storage/'.$avatar : null,
            userNickname: $player->user?->nickname,
        );
    }
}
