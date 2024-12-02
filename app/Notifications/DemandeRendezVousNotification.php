<?php

// Fichier: app/Notifications/DemandeRendezVousNotification.php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemandeRendezVousNotification extends Notification
{
    protected $rendezVous;

    public function __construct(RendezVous $rendezVous)
    {
        $this->rendezVous = $rendezVous;
    }

    public function via($notifiable)
    {
        return ['mail']; // On envoie la notification par mail
    }

    public function toMail($notifiable)
    {
        $clientName = $this->rendezVous->client ? $this->rendezVous->client->name : 'Client inconnu';
        $typeRendezVousName = $this->rendezVous->type_rendezvous ? $this->rendezVous->type_rendezvous->name : 'Type de rendez-vous inconnu';
    
        return (new MailMessage)
                    ->subject('Nouvelle demande de rendez-vous')
                    ->greeting('Bonjour ' . $notifiable->name)
                    ->line('Vous avez reçu une nouvelle demande de rendez-vous.')
                    ->line('Détails du rendez-vous:')
                    ->line('Jour: ' . $this->rendezVous->jour)
                    ->line('Duree: ' . $this->rendezVous->duree)
                    ->line('Delai pres-reservation: ' . $this->rendezVous->delaiPreReservation)
                    ->line('Interval de planification: ' . $this->rendezVous->intervalPlanification)
                    ->line('Client: ' . $this->rendezVous->client->name)
                    ->line('Type de rendez-vous: ' . $this->rendezVous->type_rendezvous_id->nomService)
                    ->line('Statut: ' . $this->rendezVous->statut)
                    ->action('Voir le rendez-vous', url('/rendezvous/' . $this->rendezVous->id));
    }
    

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
