<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'is_admin',
        'is_disabled',
        'avatar',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the display name (nickname or name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?? $this->name;
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(PlayerRating::class);
    }

    public function matchPlayers(): HasMany
    {
        return $this->hasMany(MatchPlayer::class);
    }

    public function createdMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'created_by_user_id');
    }

    public function getRatingForGame(int $gameId): ?PlayerRating
    {
        return $this->ratings()->where('game_id', $gameId)->first();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_disabled' => 'boolean',
        ];
    }
}
