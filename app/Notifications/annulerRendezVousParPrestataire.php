<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Google\Service\CloudControlsPartnerService\Console;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnulerRendezVousParPrestataire extends Notification
{
    use Queueable;

    protected $rendezVous;
    protected $raison;

    public function __construct(RendezVous $rendezVous, ?string $raison = null)
    {
        $this->rendezVous = $rendezVous;
        $this->raison = $raison;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {

        $this->rendezVous->load(['client', 'type_rendezvous', 'disponibilite.prestataire']);


        $clientName = $this->rendezVous->client?->name ?? 'Client inconnu';
        $typeRendezVousName = $this->rendezVous->type_rendezvous?->nomService ?? 'Type inconnu';
        $prestataireName = $this->rendezVous->disponibilite->prestataire?->name ?? 'Prestataire inconnu';
        $jour = $this->rendezVous->disponibilite?->jour ?? 'Jour inconnu';
        $heureDebut = $this->rendezVous->heureDebut;
        $heureFin = $this->rendezVous->heureFin;


        return (new MailMessage)
                    ->subject('Annulation de rendez-vous par urgence')
                    ->greeting('Bonjour ' . $notifiable->name)
                    ->line('Votre rendez-vous du ' . $this->rendezVous->jour . ' à ' . $this->rendezVous->heureDebut . ' a été annulé par le prestataire.')
                    ->line('Détails du rendez-vous:')
                    ->line('Jour: ' . $jour)
                    ->line('Heure de début: ' . $heureDebut)
                    ->line('Heure de fin: ' . $heureFin)
                    ->line('Durée: ' . $this->rendezVous->duree . ' minutes')
                    ->line('Délai pré-réservation: ' . $this->rendezVous->delaiPreReservation . ' jours')
                    ->line('Intervalle de planification: ' . $this->rendezVous->intervalPlanification . ' jours')
                    ->line('Durée avant annulation: ' . $this->rendezVous->dureeAvantAnnulation . ' jours')
                    ->line('Client: ' . $clientName)
                    ->line('Type de rendez-vous: ' . $typeRendezVousName)
                    ->line('Prestataire: ' . $prestataireName)
                    ->line('Statut: ' . $this->rendezVous->statut)
                    ->line('Raison de l\'annulation: ' . ($this->raison ?? 'Aucune raison fournie'))
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
