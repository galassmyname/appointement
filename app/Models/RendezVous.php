<?php

namespace App\Models;

use App\Models\Disponibilite;
use App\Models\Statistique;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RendezVous extends Model
{
    use HasFactory;

    protected $table = 'rendez_vous';
    
    protected $fillable = [
        'duree', 
        'delaiPreReservation',
        'intervalPlanification',
        'dureeAvantAnnulation',
        'disponibilite_id', // Ajouté
        
        'type_rendezvous_id',
        'client_id',
        'prestataire_id',
        'status', // Ajouté
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class, 'prestataire_id');
    }

    public function type_rendezvous()
    {
        return $this->belongsTo(TypeRendezVous::class, 'type_rendezvous_id');
    }

    public function recurrence()
    {
        return $this->belongsTo(Recurrence::class, 'type_rendezvous_id');
    }

    public function statistiques()
    {
        return $this->hasMany(Statistique::class);
    }
    public function notifications()
    {
        return $this->hasMany(Statistique::class);
    }

    // Relation avec Disponibilite
    public function disponibilite()
    {
        return $this->belongsTo(Disponibilite::class);
    }
}
