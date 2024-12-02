<?php

// app/Models/Notification.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['message', 'type', 'dateEnvoi', 'envoye', 'rendez_vous_id'];

    public function rendezVous()
    {
        return $this->belongsTo(RendezVous::class);
    }

    public function programmer()
    {
        // Implémentation pour programmer l'envoi de la notification
    }

    public function envoyer()
    {
        // Implémentation pour envoyer la notification
    }

    public function annuler()
    {
        // Implémentation pour annuler la notification
    }
}

