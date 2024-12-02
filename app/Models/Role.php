<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['nom'];

    // Relation avec User
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Relation avec Prestataire
    public function prestataires()
    {
        return $this->hasMany(Prestataire::class);
    }
}
