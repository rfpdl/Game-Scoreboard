<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MatchPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'match_id',
        'user_id',
        'team',
        'result',
        'placement',
        'rating_before',
        'rating_after',
        'rating_change',
        'confirmed_at',
    ];

    public function isTeamA(): bool
    {
        return $this->team === 'team_a';
    }

    public function isTeamB(): bool
    {
        return $this->team === 'team_b';
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }

    public function isWinner(): bool
    {
        return $this->result === 'win';
    }

    public function isLoser(): bool
    {
        return $this->result === 'lose';
    }

    public function isPending(): bool
    {
        return $this->result === 'pending';
    }

    protected function casts(): array
    {
        return [
            'rating_before' => 'integer',
            'rating_after' => 'integer',
            'rating_change' => 'integer',
            'placement' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }
}
