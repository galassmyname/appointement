<?php

namespace App\Models;

// app/Models/Statistiques.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Statistique extends Model
{
    use HasFactory;

    protected $fillable = ['totalRendezVous', 'tauxAnnulation', 'tauxSatisfaction'];

    public function genererRapport()
    {
        // Implémentation pour générer le rapport
    }

    public function exporter()
    {
        // Implémentation pour exporter les statistiques sous forme de rapport
    }
}
