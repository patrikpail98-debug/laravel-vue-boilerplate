<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Vas overovaci kod')
            ->greeting('Dobrý deň, ' . $notifiable->name . '!')
            ->line('Váš kód pre dvojfaktorové overenie je:')
            ->line($notifiable->two_factor_email_code)
            ->line('Kód je platný 10 minút.')
            ->line('Ak ste o tento kód nežiadali, nie je potrebné robiť žiadnu ďalšiu akciu.')
            ->salutation('S pozdravom, ' . config('app.name'));
    }

    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}
