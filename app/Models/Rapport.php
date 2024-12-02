<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rapport extends Model
{
    use HasFactory;

    // Table associée à ce modèle
    protected $table = 'rapports';

    // Colonnes que vous pouvez remplir en masse
    protected $fillable = [
        'prestataire_id',
        'totalRendezVous',
        'tauxAnnulation',
        'tauxOccupationPrestataire',
        'dateRapport',
    ];

    // Relation avec le modèle Prestataire (si besoin)
    public function prestataire()
    {
        return $this->belongsTo(Prestataire::class);
    }
}
