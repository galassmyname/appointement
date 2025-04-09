<?php

// app/Models/Disponibilite.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class Disponibilite extends Model
{
    use HasFactory;

    protected $fillable = [
        'jour',
        'heureDebut', 
        'heureFin', 
        'prestataire_id', 
        'estDisponible'
    ];

    public function prestataire()
    {
        // return $this->belongsTo(Prestataire::class);
        //remplacer par user pour pointer vers la table users
        return $this->belongsTo(User::class, 'prestataire_id');
    }

    //La methode pour calculer les plage horaires possible

    public function calculerPlagesHoraires($duree)
    {
        $options = collect();
        $heureDebut = Carbon::parse($this->heureDebut); 
        $heureFin = Carbon::parse($this->heureFin);     
    
        $duree = (int) $duree; // Assurez-vous que la durée est un entier
    
        Log::info('Calcul des plages horaires', [
            'heureDebut' => $heureDebut,
            'heureFin' => $heureFin,
            'duree' => $duree,
        ]);
    
        $current = $heureDebut->copy(); // Clone l'heure de début pour éviter les modifications directes
    
        while ($current->lte($heureFin)) {
            $options[$current->format('H:i')] = $current->format('H:i');
            $current->addMinutes($duree); // Passe à la plage horaire suivante
        }
    
        Log::info('Plages horaires calculées', ['options' => $options->toArray()]);
    
        return $options->toArray();
    }
    
    
    
    
}
