<?php

// app/Models/TypeRendezVous.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeRendezVous extends Model
{
    use HasFactory;


    protected $table = 'type_rendez_vous';

    
    protected $fillable = [
        'nomService', 
        'description', 
        'priorite'
    ];


    public function rendezVous()
    {
        return $this->hasMany(RendezVous::class, 'type_rendezvous_id');
    }

    public function definirPriorite($priorite)
    {
        $this->priorite = $priorite;
        $this->save();
    }


}

