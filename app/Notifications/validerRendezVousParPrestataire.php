<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ValiderRendezVousParPrestataire extends Notification
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
        
        $this->rendezVous->load(['client', 'type_rendezvous', 'disponibilite.prestataire']);
        
       
        $clientName = $this->rendezVous->client ? $this->rendezVous->client->name : 'Client inconnu';
        $typeRendezVousName = $this->rendezVous->type_rendezvous ? $this->rendezVous->type_rendezvous->nomService : 'Type de rendez-vous inconnu';
        $prestataireName = $this->rendezVous->disponibilite->prestataire ? $this->rendezVous->disponibilite->prestataire->name : 'Prestataire inconnu';
        $jour = $this->rendezVous->disponibilite ? $this->rendezVous->disponibilite->jour : 'Jour inconnu'; 
        $heureDebut = $this->rendezVous->heureDebut; 
        $heureFin = $this->rendezVous->heureFin; 
        
        return (new MailMessage)
                    ->subject('Validation de rendez-vous')
                    ->greeting('Bonjour ' . $notifiable->name)
                    ->line('Votre demande de rendez-vous du ' . $this->rendezVous->jour . ' à ' . $this->rendezVous->heureDebut . ' a été valider par le prestataire.')
                    ->line('Détails du rendez-vous:')
                    ->line('Jour: ' . $jour)
                    ->line('Heure de début: ' . $heureDebut) 
                    ->line('Heure de fin: ' . $heureFin) 
                    ->line('Durée: ' . $this->rendezVous->duree . ' minutes')
                    ->line('Délai pré-réservation: ' . $this->rendezVous->delaiPreReservation . ' minutes')
                    ->line('Intervalle de planification: ' . $this->rendezVous->intervalPlanification . ' jours')
                    ->line('Durée avant annulation: ' . $this->rendezVous->dureeAvantAnnulation . ' minutes')
                    ->line('Client: ' . $clientName)
                    ->line('Type de rendez-vous: ' . $typeRendezVousName)
                    ->line('Prestataire: ' . $prestataireName)
                    ->line('Statut: ' . $this->rendezVous->statut)
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
