<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'uuid',
        'game_id',
        'match_code',
        'match_format',
        'max_players',
        'match_type',
        'name',
        'scheduled_at',
        'share_token',
        'status',
        'created_by_user_id',
        'played_at',
    ];

    public static function generateMatchCode(): string
    {
        do {
            $code = mb_strtoupper(Str::random(6));
        } while (self::where('match_code', $code)->exists());

        return $code;
    }

    public static function generateShareToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('share_token', $token)->exists());

        return $token;
    }

    public function getShareUrl(): string
    {
        return route('matches.joinByShareToken', ['token' => $this->share_token]);
    }

    public function isQuick(): bool
    {
        return $this->match_type === 'quick';
    }

    public function isBooked(): bool
    {
        return $this->match_type === 'booked';
    }

    public function is1v1(): bool
    {
        return $this->match_format === '1v1';
    }

    public function is2v2(): bool
    {
        return $this->match_format === '2v2';
    }

    public function isFFA(): bool
    {
        return $this->match_format === 'ffa';
    }

    public function isTeamMatch(): bool
    {
        return $this->match_format === '2v2';
    }

    public function hasRoom(): bool
    {
        return $this->players()->count() < $this->max_players;
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at !== null;
    }

    public function isReadyToPlay(): bool
    {
        if (! $this->isBooked()) {
            return true;
        }

        return $this->scheduled_at && $this->scheduled_at->isPast();
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(MatchPlayer::class, 'match_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getWinner(): ?MatchPlayer
    {
        return $this->players()->where('result', 'win')->first();
    }

    public function getLoser(): ?MatchPlayer
    {
        return $this->players()->where('result', 'lose')->first();
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function (GameMatch $match): void {
            if (empty($match->uuid)) {
                $match->uuid = (string) Str::uuid();
            }

            if (empty($match->match_code)) {
                $match->match_code = self::generateMatchCode();
            }

            if (empty($match->share_token)) {
                $match->share_token = self::generateShareToken();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'max_players' => 'integer',
        ];
    }
}
