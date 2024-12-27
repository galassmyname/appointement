<?php

namespace App\Models;

use App\Mail\StatutRendezVousChange;
use App\Models\Disponibilite;
use App\Models\Statistique;
use App\Notifications\RendezVousClientNotificationByAdmin;
use App\Notifications\RendezVousPrestataireNotificationByAdmin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class RendezVous extends Model
{
    use HasFactory;
    use  SoftDeletes; 

    protected $table = 'rendez_vous';
    
    protected $fillable = [
        'duree', 
        'delaiPreReservation',
        'intervalPlanification',
        'nombre_jours',
        'date_debut',
        'date_fin',
        'dureeAvantAnnulation',
        'disponibilite_id',
        'type_rendezvous_id',
        'client_id',
        'prestataire_id',
        'statut' => 'en attente',
        'heureDebut', // Ajouté
        'heureFin',   // Ajouté
    ];
    

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class);
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
        return $this->belongsTo(Disponibilite::class, 'disponibilite_id');
    }

    // protected static function booted()
    // {
    //     parent::booted();
    
    //     static::deleting(function ($rendezVous) {
    //         // Charger le prestataire lié au rendez-vous
    //         $prestataire = $rendezVous->prestataire;
    
    //         if ($prestataire) {
    //             // Supprimer toutes les réservations liées au prestataire
    //             $prestataire->reservations()->delete();
    //         }
    //     });
    // }
    


    protected static function booted()
    {
        parent::booted();
    
        static::deleting(function ($rendezVous) {
            // Charger le prestataire lié au rendez-vous
            $prestataire = $rendezVous->prestataire;
    
            if ($prestataire) {
                // Supprimer toutes les réservations liées au prestataire
                $prestataire->reservations()->delete();
            }
        });

        static::created(function ($rendezVous) {
            // Charger les relations nécessaires
            $rendezVous->load(['client', 'prestataire', 'disponibilite', 'type_rendezvous']);
            
            $client = User::find($rendezVous->client_id);
            $prestataire = User::find($rendezVous->prestataire_id);
            //$disponibilite = User::find($rendezVous->disponibilite_id);
    
            if ($client) {
                $client->notify(new RendezVousClientNotificationByAdmin($rendezVous));
            }
            if ($prestataire) {
                $prestataire->notify(new RendezVousPrestataireNotificationByAdmin($rendezVous));
            }
        });

        static::updated(function (RendezVous $rendezVous) {
            // Vérifiez si le champ 'statut' a été modifié
            if ($rendezVous->wasChanged('statut')) {
                // Appeler la méthode pour envoyer les notifications
                $rendezVous->notifyStatusChange();
            }
        });
    }

       // Fonction pour envoyer les notifications par mail
       public function notifyStatusChange()
       {
           // Récupérer le client et le prestataire associés au rendez-vous
           $client = User::find($this->client_id);
           $prestataire = User::find($this->prestataire_id);
       
           // Vérifier si le client et le prestataire existent
           if ($client && $prestataire) {
               // Déterminer le message en fonction du statut
               if ($this->statut === 'validé') {
                   $message = "Votre rendez-vous vient d'être validé par l'administrateur.";
               } elseif ($this->statut === 'annulé') {
                   $message = "Votre rendez-vous vient d'être annulé par l'administrateur.";
               }
       
               // Envoyer un email au client
               try {
                   Mail::to($client->email)->send(new StatutRendezVousChange($this, $client, $message));
               } catch (\Exception $e) {
                   Log::error('Erreur lors de l\'envoi de l\'email au client', ['message' => $e->getMessage()]);
               }
       
               // Envoyer un email au prestataire
               try {
                   Mail::to($prestataire->email)->send(new StatutRendezVousChange($this, $prestataire, $message));
               } catch (\Exception $e) {
                   Log::error('Erreur lors de l\'envoi de l\'email au prestataire', ['message' => $e->getMessage()]);
               }
           }
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
