<?php

namespace App\Mail;

use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StatutRendezVousChange extends Mailable
{
    use Queueable, SerializesModels;

    public $rendezVous;
    public $user;
    public $message;

    /**
     * Create a new message instance.
     *
     * @param  RendezVous  $rendezVous
     * @param  User  $user
     * @param  string  $message
     * @return void
     */
    public function __construct(RendezVous $rendezVous, User $user, string $message)
    {
        $this->rendezVous = $rendezVous;
        $this->user = $user;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Changement de statut de votre rendez-vous')
                    ->view('emails.rendezvous_statut_change'); // Assurez-vous que la vue correspond bien
    }
}
