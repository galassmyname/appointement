<?php

namespace App\Http\Controllers;

use App\Models\Disponibilite;

use App\Models\Prestataire;
use App\Models\RendezVous;
use App\Models\User;
use App\Notifications\AnnulationRendezVousNotification;
use App\Notifications\RendezVousValideNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class DisponibiliteController extends Controller
{
    

    public function definirDisponibilites(Request $request)
    {
        // Validation des données
        $request->validate([
            'jour' => 'required|date', // Remplacement de 'date' par 'jour'
            'heureDebut' => 'required|date_format:H:i',
            'heureFin' => 'required|date_format:H:i|after:heureDebut',
            'estDisponible' => 'required|boolean',
        ]);
    
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
    
        // Vérifier si un prestataire est bien connecté
        if (!$prestataire) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
    
        // Créer ou mettre à jour la disponibilité
        try {
            $disponibilite = Disponibilite::updateOrCreate(
                [
                    'jour' => $request->jour, // Utilisation de 'jour' au lieu de 'date'
                    'heureDebut' => $request->heureDebut,
                    'heureFin' => $request->heureFin,
                    'prestataire_id' => $prestataire->id,
                ],
                [
                    'estDisponible' => $request->estDisponible,
                ]
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    
        return response()->json([
            'message' => 'Disponibilités définies avec succès',
            'disponibilite' => $disponibilite
        ]);
    }
    

    //Methode pour lister les disponibilites du  prestataire connecter
    public function listerDisponibilites()
    {
        try {
            // Authentifier le prestataire à partir du token JWT
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
        
        // Vérifier si le prestataire est authentifié
        if (!$prestataire) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        
        // Récupérer les disponibilités du prestataire connecté
        try {
            $disponibilites = Disponibilite::where('prestataire_id', $prestataire->id)
                                          ->get();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        
        // Vérifier s'il n'y a pas de disponibilités
        if ($disponibilites->isEmpty()) {
            return response()->json(['message' => 'Aucune disponibilité trouvée'], 404);
        }
        
        return response()->json([
            'message' => 'Disponibilités récupérées avec succès',
            'disponibilites' => $disponibilites
        ]);
    }
    
    
    
}
