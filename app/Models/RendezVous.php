<?php

namespace App\Models;

use App\Models\Disponibilite;
use App\Models\Statistique;
use App\Notifications\RendezVousClientNotificationByAdmin;
use App\Notifications\RendezVousPrestataireNotificationByAdmin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RendezVous extends Model
{
    use HasFactory;
    use  SoftDeletes; 

    protected $table = 'rendez_vous';
    
    protected $fillable = [
        'duree', 
        'delaiPreReservation',
        'intervalPlanification',
        'dureeAvantAnnulation',
        'disponibilite_id',
        'type_rendezvous_id',
        'client_id',
        'prestataire_id',
        'status' => 'en attente',
        'heureDebut', // Ajouté
        'heureFin',   // Ajouté
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

    protected static function booted()
    {
        static::created(function ($rendezVous) {
            // Envoyer des notifications après la création
            $client = User::find($rendezVous->client_id);
            $prestataire = User::find($rendezVous->prestataire_id);

            if ($client) {
                $client->notify(new RendezVousClientNotificationByAdmin($rendezVous));
            }
            if ($prestataire) {
                $prestataire->notify(new RendezVousPrestataireNotificationByAdmin($rendezVous));
            }
        });
    }


    public static function hasConflict($prestataireId, $heureDebut, $heureFin)
    {
        return self::where('prestataire_id', $prestataireId)
            ->where(function ($query) use ($heureDebut, $heureFin) {
                $query->whereBetween('heureDebut', [$heureDebut, $heureFin])
                      ->orWhereBetween('heureFin', [$heureDebut, $heureFin])
                      ->orWhere(function ($q) use ($heureDebut, $heureFin) {
                          $q->where('heureDebut', '<=', $heureDebut)
                            ->where('heureFin', '>=', $heureFin);
                      });
            })
            ->exists();
    }
    
    
}
