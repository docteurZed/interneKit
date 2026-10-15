<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomResetPassword extends Notification
{
    use Queueable;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.frontend_url') . "/password-reset/{$this->token}?email=" . urlencode($notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject("Réinitialisation de votre mot de passe")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation de votre mot de passe.")
            ->action("Réinitialiser mon mot de passe", $url)
            ->line("Ce lien est valable seulement pendant 60 minutes.")
            ->line("Si vous n'avez pas demandé de réinitialisation, vous pouvez ignorer ce message en toute sécurité.")
            ->salutation("Cordialement, L'équipe");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
