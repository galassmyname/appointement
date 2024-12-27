<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'prestataire_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->password)) {
                // generer un mot de passe aleatoire
                $randomPassword = Str::random(12);
                $user->password = Hash::make($randomPassword);

                // envoie le mot de passe par mail
                Mail::to($user->email)->send(new \App\Mail\PrestatairePasswordMail($user->name, $randomPassword, $user->email));

            }
        });
    }

}

