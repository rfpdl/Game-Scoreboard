<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class UserData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $nickname,
        public readonly string $displayName,
        public readonly string $email,
        public readonly bool $isAdmin,
        public readonly ?string $avatar,
        public readonly ?string $emailVerifiedAt,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            nickname: $user->nickname,
            displayName: $user->display_name,
            email: $user->email,
            isAdmin: $user->is_admin,
            avatar: $user->avatar,
            emailVerifiedAt: $user->email_verified_at?->toISOString(),
        );
    }
}
