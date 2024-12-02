<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Prestataire extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'name', 
        'email', 
        'telephone', 
        'specialite', 
        'is_admin', 
        'role_id', 
        'password'
    ];

    protected $hidden = [
        'password',
    ];

    // JWT methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function disponibilites()
    {
        return $this->hasMany(Disponibilite::class);
    }
    

    public function rendez_vous()
    {
        return $this->hasMany(RendezVous::class);
    }

}

