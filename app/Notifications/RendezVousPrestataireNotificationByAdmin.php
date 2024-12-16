<?php



namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RendezVousPrestataireNotificationByAdmin extends Notification
{
    use Queueable;

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
        $jourDisponibilite = $this->rendezvous->disponibilite->jour ?? 'Non spécifié';
        return (new MailMessage)
            ->subject('Nouveau rendez-vous confirmé')
            ->view('emails.prestataireRendezvousByAdmin', [
                'rendezvous' => $this->rendezvous,
                'prestataire' => $notifiable,
            ]);
    }
    
}
