<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RendezVousNotification extends Notification
{
    use Queueable;

    protected $rendezVous;

    /**
     * Create a new notification instance.
     *
     * @param RendezVous $rendezVous
     */
    public function __construct(RendezVous $rendezVous)
    {
        $this->rendezVous = $rendezVous;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->greeting("Bonjour, {$notifiable->name}")
            ->line("Un rappel pour votre rendez-vous prévu à {$this->rendezVous->heureDebut}.")
            ->line("Date : {$this->rendezVous->jour}")
            ->action('Voir les détails', url('/rendez-vous'))
            ->line('Merci de votre confiance !');
    }
}
