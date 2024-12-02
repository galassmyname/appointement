<?php

// app/Models/Recurrence.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurrence extends Model
{
    use HasFactory;

    protected $fillable = ['typeRecurrence', 'dateDebut', 'dateFin', 'interval'];

    public function calculateProchaineOccurrence()
    {
        // Implémentation de la logique pour calculer la prochaine occurrence
    }
}
