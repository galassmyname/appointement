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
        'role',
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



    // public function role()
    // {
    //     return $this->belongsTo(Role::class, 'role', 'name');
    // }

    // Relation entre l'utilisateur et ses disponibilités
    public function disponibilites()
    {
        return $this->hasMany(Disponibilite::class, 'prestataire_id');
    }

    // Relation entre l'utilisateur et son rôle
    // public function role()
    // {
    //     return $this->belongsTo(Role::class);
    // }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Relation entre l'utilisateur et ses réservations
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'prestataire_id');
    }

    // Relation entre l'utilisateur et ses rendez-vous
    public function rendez_vous()
    {
        return $this->hasMany(RendezVous::class, 'prestataire_id');
    }

    // Méthode requise par JWTSubject pour obtenir l'identifiant JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Méthode requise par JWTSubject pour obtenir les revendications personnalisées JWT
    public function getJWTCustomClaims()
    {
        return [];
    }

    // Surcharge de la méthode boot pour ajouter des comportements spécifiques lors de la création d'un utilisateur
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Vérifier si le mot de passe est vide
            if (empty($user->password)) {
                // Générer un mot de passe aléatoire
                $randomPassword = Str::random(12);
                $user->password = Hash::make($randomPassword);

                // Si le rôle est mis à jour, synchroniser avec Spatie
                if ($user->isDirty('role_id')) {
                    $role = Role::find($user->role_id);
                    if ($role) {
                        // $user->syncRoles([$role->name]);
                        $user->role = $role->name; // Met à jour le champ "role" dans la table users
                    }
                }
                // Envoyer le mot de passe par email
                Mail::to($user->email)->send(new \App\Mail\SendUserPassword($user->name, $randomPassword, $user->email));
            }

            // Si le rôle n'est pas déjà défini (cas d'auto-inscription d'un utilisateur)
            if (empty($user->role) && empty($user->role_id)) {
                // Attribuer le rôle "utilisateur" par défaut
                $defaultRole = Role::where('name', 'utilisateur')->first();
                if ($defaultRole) {
                    $user->role_id = $defaultRole->id;
                    $user->role = 'utilisateur';
                    // Utiliser la méthode assignRole après la sauvegarde, car elle nécessite un ID
                    $user->assignRole('utilisateur');
                }
            }

            // Si role_id est défini par un admin mais que role ne l'est pas encore
            elseif (!empty($user->role_id) && empty($user->role)) {
                // Récupérer et stocker le nom du rôle
                $role = Role::find($user->role_id);
                if ($role) {
                    $user->role = $role->name;
                }
            }
        });

        static::updating(function ($user) {
            // Si le rôle est mis à jour, synchroniser avec Spatie
            if ($user->isDirty('role_id')) {
                $role = Role::find($user->role_id);
                if ($role) {
                    // $user->syncRoles([$role->name]);
                    $user->role = $role->name; // Met à jour le champ "role" dans la table users
                }
            }
        });
    }
}
