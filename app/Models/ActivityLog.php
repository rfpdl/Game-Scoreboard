<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public static function log(
        int $userId,
        string $action,
        string $subjectType,
        ?int $subjectId = null,
        ?string $subjectName = null,
        ?array $changes = null,
        ?string $ipAddress = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'subject_name' => $subjectName,
            'changes' => $changes,
            'ip_address' => $ipAddress,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDescriptionAttribute(): string
    {
        $action = match ($this->action) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'toggled_admin' => 'toggled admin status for',
            'toggled_active' => 'toggled active status for',
            default => $this->action,
        };

        $subject = $this->subject_name ?? sprintf('%s #%s', $this->subject_type, $this->subject_id);

        return sprintf('%s %s: %s', $action, $this->subject_type, $subject);
    }
}
