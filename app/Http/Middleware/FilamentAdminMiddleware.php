<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilamentAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Vérifier si l'utilisateur est connecté
        if (!$user) {
            return redirect()->route('filament.admin.auth.login');
        }
        
        // Vérifier si l'utilisateur est administrateur
        // Vous pouvez adapter cette logique selon votre système de rôles
        if (!$this->canAccessFilament($user)) {
            abort(403, 'Accès non autorisé au panel d\'administration.');
        }
        
        return $next($request);
    }
    
    private function canAccessFilament($user): bool
    {
        // Option 1 : Vérifier le champ is_admin
        if (isset($user->is_admin) && $user->is_admin) {
            return true;
        }
        
        // Option 2 : Vérifier le rôle via Spatie
        if ($user->hasRole(['admin', 'administrateur', 'super-admin'])) {
            return true;
        }
        
        // Option 3 : Vérifier le champ role directement
        if (in_array($user->role, ['admin', 'administrateur', 'super-admin'])) {
            return true;
        }
        
        // Option 4 : Permettre l'accès à tous les utilisateurs (temporaire pour debug)
        // return true;
        
        return false;
    }
}