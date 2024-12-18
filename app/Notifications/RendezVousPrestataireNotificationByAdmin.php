<?php



namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RendezVousPrestataireNotificationByAdmin extends Notification
{
    protected $rendezvous;

    public function __construct($rendezvous)
    {
        $this->rendezvous = $rendezvous;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $rendezvous = $this->rendezvous; 
        $prestataire = $rendezvous->prestataire; 
        $client = $rendezvous->client; 
        $disponibilite = $rendezvous->disponibilite; 
        $type_rendezvous = $rendezvous->type_rendezvous; 

        return (new MailMessage)
            ->subject('Confirmation de rendez-vous')
            ->greeting('Bonjour ' . $rendezvous->prestataire->name . ',')
            ->line('Un nouveau rendez-vous a été confirmé.')
            ->line('Voici les détails du rendez-vous :')
            ->line('**Client :** ' . $rendezvous->client->name)
            ->line('**Email du client :** ' . $rendezvous->client->email)
            ->line('**Téléphone du client :** ' . optional($rendezvous->client)->telephone ?? 'Non spécifié')
            ->line('**Jour de disponibilité :** ' . optional($rendezvous->disponibilite)->jour ?? 'Non spécifié')
            ->line('**Date et heure de début :** ' . $rendezvous->heureDebut)
            ->line('**Date et heure de fin :** ' . $rendezvous->heureFin)
            ->line('**Type de rendez-vous :** ' . $rendezvous->type_rendezvous->nomService)
            ->line('**Durée :** ' . $rendezvous->duree . ' minutes')
            ->line('**Statut :** ' . $rendezvous->status)
            ->line('<br><br>') // Ajout de l'espace pour le design

            // Personnalisation de la structure HTML
            ->markdown('emails.prestataireRendezvousByAdmin', [
                'rendezvous' => $rendezvous,
                'client' => $client,
                'prestataire' => $prestataire
            ]);
    }
}
