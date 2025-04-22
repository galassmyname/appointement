<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'prestataire_id',
        'jour',
        'heureDebut',
        'heureFin',
        'client_id',
    ];

    /**
     * Récupérer le prestataire associé à la réservation
     */
    public function prestataire()
    {
        return $this->belongsTo(User::class, 'prestataire_id');
    }

    /**
     * Récupérer le client associé à la réservation
     */
    public function client()
    {
        return $this->belongsTo(User::class);
    }
}
