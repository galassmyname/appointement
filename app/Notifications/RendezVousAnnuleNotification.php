<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RendezVousAnnuleNotification extends Notification
{
    use Queueable;

    protected $rendezVous;

    public function __construct(RendezVous $rendezVous)
    {
        $this->rendezVous = $rendezVous;
    }

    public function via($notifiable)
    {
        return ['mail']; 
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Rendez-vous annulé')
                    ->greeting('Bonjour ' . $notifiable->name)
                    ->line('Le rendez-vous du ' . $this->rendezVous->date . ' à ' . $this->rendezVous->heureDebut . 'avec ' . $this->rendezVous->client->name. ' a été annulé par le client.')
                    ->line('Détails du rendez-vous :')
                    ->line('Date : ' . $this->rendezVous->date)
                    ->line('Heure de début : ' . $this->rendezVous->heureDebut)
                    ->line('Heure de fin : ' . $this->rendezVous->heureFin)
                    ->line('Statut : Annulé')
                    ->action('Voir vos rendez-vous', url('/dashboard'))
                    ->line('Merci d\'avoir utilisé notre service.');
    }

    public function toArray($notifiable)
    {
        return [
            'rendezVousId' => $this->rendezVous->id,
            'message' => 'Le rendez-vous a été annulé.',
        ];
    }
}
