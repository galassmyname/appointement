<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'telephone',
        'role_id', // Ajoutez la colonne role ici
        'is_admin',
        'password',
    ];
    
// App\Models\User.php

protected static function booted()
{
    static::creating(function ($user) {
        // Vérifier si le rôle n'est pas défini
        if (!$user->role_id) {
            // Attribuer le rôle "Utilisateur" par défaut si non défini
            $role = Role::where('name', 'Utilisateur')->first();
            $user->role_id = $role ? $role->id : null; // Si le rôle existe, on l'assigne, sinon on laisse null
        }
    });
}


    protected $attributes = [
        //'role' => 'utilisateur', // Rôle par défaut
        'is_admin' => 0,         // Non-administrateur par défaut
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    
    public function role()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }
        // Méthodes requises par JWTSubject
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
        
    public function getJWTCustomClaims()
    {
        return [];
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
                Mail::to($user->email)->send(new \App\Mail\SendUserPassword($user->name, $randomPassword, $user->email));

            }
        });
    }
}
