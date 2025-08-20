<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements JWTSubject, FilamentUser
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
        'role_id',
        'is_admin',
        'password',
        'is_active',
        'specialite'
    ];

    protected static function booted()
    {
        static::creating(function ($user) {
            if (!$user->role_id) {
                $role = Role::where('name', 'Utilisateur')->first();
                $user->role_id = $role ? $role->id : null;
            }
        });
    }

    protected $attributes = [
        'is_admin' => 0,
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

    /**
     * Méthode requise par FilamentUser pour contrôler l'accès au panel Filament
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Si c'est le panel admin
        if ($panel->getId() === 'admin') {
            // Vérifier si l'utilisateur est administrateur
            return $this->is_admin || 
                   $this->hasRole(['admin', 'administrateur', 'super-admin']) ||
                   in_array($this->role, ['admin', 'administrateur', 'super-admin']);
        }
        
        return false;
    }

    // Relations existantes...
    public function disponibilites()
    {
        return $this->hasMany(Disponibilite::class, 'prestataire_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'prestataire_id');
    }

    public function rendez_vous()
    {
        return $this->hasMany(RendezVous::class, 'prestataire_id');
    }

    // Méthodes JWT existantes...
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Boot method existant...
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->password)) {
                $randomPassword = Str::random(12);
                $user->password = Hash::make($randomPassword);

                if ($user->isDirty('role_id')) {
                    $role = Role::find($user->role_id);
                    if ($role) {
                        $user->role = $role->name;
                    }
                }
                Mail::to($user->email)->send(new \App\Mail\SendUserPassword($user->name, $randomPassword, $user->email));
            }

            if (empty($user->role) && empty($user->role_id)) {
                $defaultRole = Role::where('name', 'utilisateur')->first();
                if ($defaultRole) {
                    $user->role_id = $defaultRole->id;
                    $user->role = 'utilisateur';
                    $user->assignRole('utilisateur');
                }
            }
            elseif (!empty($user->role_id) && empty($user->role)) {
                $role = Role::find($user->role_id);
                if ($role) {
                    $user->role = $role->name;
                }
            }
        });

        static::updating(function ($user) {
            if ($user->isDirty('role_id')) {
                $role = Role::find($user->role_id);
                if ($role) {
                    $user->role = $role->name;
                }
            }
        });
    }
}