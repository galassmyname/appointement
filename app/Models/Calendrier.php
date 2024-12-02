<?php

// app/Models/Calendrier.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendrier extends Model
{
    use HasFactory;

    protected $fillable = [];

    public function evenements()
    {
        return $this->hasMany(RendezVous::class);
    }

    public function afficherCalendrier()
    {
        // Implémentation pour afficher le calendrier
    }

    public function ajouterEvenement(RendezVous $rendezVous)
    {
        // Ajoute un événement au calendrier
        $this->evenements()->save($rendezVous);
    }
}
