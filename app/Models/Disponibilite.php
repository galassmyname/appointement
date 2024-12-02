<?php

// app/Models/Disponibilite.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        return $this->belongsTo(Prestataire::class);
    }
}
