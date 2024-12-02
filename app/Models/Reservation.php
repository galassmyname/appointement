<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    // Définir les champs que vous souhaitez mass-assignable
    protected $fillable = [
        'prestataire_id',
        'jour',
        'heureDebut',
        'heureFin',
        'client_id', // Par exemple, si vous avez un client qui effectue la réservation
        // Ajoutez d'autres champs ici selon vos besoins
    ];

    /**
     * Récupérer le prestataire associé à la réservation
     */
    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class);
    }

    /**
     * Récupérer le client associé à la réservation
     */
    public function client()
    {
        return $this->belongsTo(User::class); // Si vous avez un modèle Client
    }
}
