<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\GameMatch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class MatchInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public GameMatch $match,
        public User $invitedBy,
    ) {}

    /**
     * Check if mail is properly configured for sending.
     */
    public static function isMailConfigured(): bool
    {
        $mailer = config('mail.default');

        // These mailers don't actually send emails
        if (in_array($mailer, ['log', 'array', 'null'], true)) {
            return false;
        }

        // For SMTP, check if host is configured
        if ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host');

            return $host && $host !== '127.0.0.1' && $host !== 'localhost';
        }

        // Other mailers (ses, postmark, sendgrid, etc.) are assumed configured
        return true;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Only send email if mail is properly configured
        if (! self::isMailConfigured()) {
            return [];
        }

        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = Setting::get('app_name', config('branding.name'));
        $primaryColor = Setting::get('primary_color', config('branding.primary_color'));
        $gameName = $this->match->game->name;
        $matchUrl = route('matches.show', $this->match->uuid);

        return (new MailMessage)
            ->subject("{$this->invitedBy->name} invited you to play {$gameName}")
            ->greeting("You've been invited!")
            ->line("{$this->invitedBy->name} has invited you to join a {$gameName} match on {$appName}.")
            ->line("Match Code: **{$this->match->code}**")
            ->action('Join Match', $matchUrl)
            ->line('Click the button above to view the match and start playing.')
            ->salutation('Good luck!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'match_id' => $this->match->id,
            'match_uuid' => $this->match->uuid,
            'invited_by' => $this->invitedBy->id,
        ];
    }
}
