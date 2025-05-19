<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'prestataire_id',
        'date', // ✅ renommé ici
        'heureDebut',
        'heureFin',
        'client_id',
    ];

    // ✅ Optionnel : indiquer à Laravel que "date" est une instance de Carbon
    protected $casts = [
        'date' => 'date',
        'heureDebut' => 'datetime:H:i', // si stocké comme time
        'heureFin' => 'datetime:H:i',
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
