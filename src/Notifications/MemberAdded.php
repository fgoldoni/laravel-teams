<?php

declare(strict_types=1);

namespace Goldoni\LaravelTeams\Notifications;

use Goldoni\LaravelTeams\Enums\TeamRoleEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberAdded extends Notification
{
    use Queueable;

    public function __construct(public Model $team, public TeamRoleEnum $role)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You were added to a team')
            ->line('Team: ' . $this->team->getAttribute('name'))
            ->line('Role: ' . $this->role->value);
    }
}
