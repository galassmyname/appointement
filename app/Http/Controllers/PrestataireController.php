<?php

namespace App\Http\Controllers;

use App\Models\Disponibilite;

use App\Models\Prestataire;
use App\Models\RendezVous;
use App\Models\User;
use App\Notifications\AnnulationRendezVousNotification;
use App\Notifications\AnnulerRendezVousParPrestataire;
use App\Notifications\RendezVousValideNotification;
use App\Notifications\ValiderRendezVousParPrestataire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class PrestataireController extends Controller
{

     // Inscription d'un prestataire (optionnel)
     public function prestataireRegister(Request $request)
     {
         try {
             // Valider les données d'entrée
             $validator = Validator::make($request->all(), [
                 'name' => 'required|string|max:255',
                 'email' => 'required|email|unique:prestataires,email',
                 'telephone' => 'required|string|max:20',
                 'specialite' => 'required|string|max:255',
                 'role_id' => 'required|exists:roles,id',
                 'password' => 'required|string|confirmed|min:6',
             ], [
                 'name.required' => 'Le nom est obligatoire.',
                 'email.required' => 'L\'email est obligatoire.',
                 'email.unique' => 'Cet email est déjà utilisé.',
                 'telephone.required' => 'Le téléphone est obligatoire.',
                 'specialite.required' => 'La spécialité est obligatoire.',
                 'role_id.required' => 'Le rôle est obligatoire.',
                 'role_id.exists' => 'Le rôle sélectionné est invalide.',
                 'password.required' => 'Le mot de passe est obligatoire.',
                 'password.confirmed' => 'Les mots de passe ne correspondent pas.',
             ]);
     
             // Vérifier si la validation a échoué
             if ($validator->fails()) {
                 return response()->json([
                     'status' => 'error',
                     'message' => 'Validation des données échouée.',
                     'errors' => $validator->errors()
                 ], 400);
             }
     
             // Créer le prestataire
             $prestataire = Prestataire::create([
                 'name' => $request->name,
                 'email' => $request->email,
                 'telephone' => $request->telephone,
                 'specialite' => $request->specialite,
                 'password' => Hash::make($request->password),
                 'role_id' => $request->role_id,
                 'is_admin' => false,
             ]);
     
             $token = Auth::guard('prestataire')->login($prestataire);
     
             return response()->json([
                 'status' => 'success',
                 'message' => 'Inscription réussie.',
                 'data' => [
                     'prestataire' => $prestataire,
                     'token' => $token
                 ]
             ], 201);
     
         } catch (\Exception $e) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Une erreur inattendue s\'est produite.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
     




     
    // Connexion d'un prestataire
    public function prestataireLogin(Request $request)
    {
        try {
            // Validation des données d'entrée
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:6',
            ], [
                'email.required' => 'Le champ email est obligatoire.',
                'email.email' => 'L\'adresse email fournie est invalide.',
                'password.required' => 'Le champ mot de passe est obligatoire.',
            ]);
    
            // Tentative d'authentification avec le guard 'prestataire'
            if (!$token = auth('prestataire')->attempt($request->only('email', 'password'))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email ou mot de passe incorrect.',
                ], 401);
            }
    
            // Récupération du prestataire authentifié
            $prestataire = auth('prestataire')->user();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Connexion réussie.',
                'data' => [
                    'prestataire' => $prestataire,
                    'token' => $token,
                ]
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation des données échouée.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    
    

    
    public function refreshToken()
    {
        try {
            $token = JWTAuth::getToken();
    
            if (!$token) {
                return response()->json(['error' => 'Token non fourni'], 401);
            }
    
            
            $newToken = JWTAuth::refresh($token);
    
            $cookie = cookie(
                'refresh_token', 
                $newToken, // Valeur du refresh token
                1440, // Expiration en minutes (14 jours)
                '/', // Chemin
                null, // Domaine
                true, // HTTPS uniquement
                true // HTTP-only
            );
    
            return response()->json([
                'message' => 'Token rafraîchi avec succès',
            ])->cookie($cookie);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Le token a expiré et ne peut pas être rafraîchi'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Erreur lors du rafraîchissement du token'], 500);
        }
    }


    
    // Déconnexion d'un prestataire
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();
    
            if ($token) {
                JWTAuth::invalidate($token); 
            }
    
            $cookie = cookie('refresh_token', '', -1); 
    
            return response()->json(['message' => 'Déconnexion réussie'])->cookie($cookie);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    


    // Méthode pour permettre au prestataire de définir ses disponibilités
    public function store(Request $request)
    {
        $request->validate([
            'jour' => 'required|string|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi',
            'heureDebut' => 'required|date_format:H:i',
            'heureFIn' => 'required|date_format:H:i|after:heureDebut',
        ]);

        $disponibilite = Disponibilite::create([
            'prestataire_id' => JWTAuth::parseToken()->authenticate(),
            'jour' => $request->jour,
            'heureDebut' => $request->heureDebut,
            'heureFIn' => $request->heureFIn,
            'status' => true, // Par défaut actif
        ]);

        return response()->json(['message' => 'Disponibilité créée avec succès', 'data' => $disponibilite], 201);
    }

// Méthode pour permettre au prestataire de définir ses disponibilités
    public function definirDisponibilites(Request $request)
    {
        
        $request->validate([
            'jour' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi',
            'heureDebut' => 'required|date_format:H:i',
            'heureFin' => 'required|date_format:H:i|after:heureDebut',
            //'estDisponible' => 'required|boolean',
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

        if (!$prestataire) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        // Créer ou mettre à jour la disponibilité pour un jour précis de la semaine
        try {
            $disponibilite = Disponibilite::updateOrCreate(
                [
                    'jour' => $request->jour,  
                    'prestataire_id' => $prestataire->id,
                ],
                [
                    'heureDebut' => $request->heureDebut,
                    'heureFin' => $request->heureFin,
                    'estDisponible' => true,
                ]
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Disponibilité pour le jour ' . $request->jour . ' définie avec succès',
            'disponibilite' => $disponibilite
        ]);
    }




    //Methode pour lister les creanaux horaires
    public function listerDisponibilites(Request $request)
    {
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
            if (!$prestataire) {
                return response()->json(['error' => 'Token invalide ou utilisateur non trouvé'], 401);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
    
        
        if (!$prestataire) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
    
        try {
            
            $disponibilites = Disponibilite::where('prestataire_id', $prestataire->id)->get();
    
            // Si aucune disponibilité n'est trouvée
            if ($disponibilites->isEmpty()) {
                return response()->json(['message' => 'Aucune disponibilité trouvée pour ce prestataire'], 404);
            }
    
            
            return response()->json([
                'message' => 'Disponibilités récupérées avec succès',
                'disponibilites' => $disponibilites
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    


    //Methode pour modifier une disponibilite
    public function modifierDisponibilite(Request $request, $id)
    {
        try {
            
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
    
        
        if (!$prestataire) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
    
        try {
            
            $disponibilite = Disponibilite::find($id);
    
            
            if (!$disponibilite) {
                return response()->json(['message' => 'Disponibilité introuvable'], 404);
            }
    
            if ($disponibilite->prestataire_id != $prestataire->id) {
                return response()->json(['message' => 'Vous ne pouvez modifier que vos propres disponibilités'], 403);
            }
    
            
            $disponibilite->update($request->all());
    
            return response()->json([
                'message' => 'Disponibilité modifiée avec succès',
                'disponibilite' => $disponibilite
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
    
    
    
    //Methode pour supprimer un crenaux horaire
    public function supprimerDisponibilite($id)
    {
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
    
        
        if (!$prestataire) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
    
        try {
            $disponibilite = Disponibilite::find($id);

            if (!$disponibilite) {
                return response()->json(['message' => 'Disponibilité introuvable'], 404);
            }
    
            if ($disponibilite->prestataire_id != $prestataire->id) {
                return response()->json(['message' => 'Vous ne pouvez supprimer que vos propres disponibilités'], 403);
            }
    
            
            $disponibilite->delete();
    
            return response()->json(['message' => 'Disponibilité supprimée avec succès']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
        



    public function listerRendezVousPrestataire(Request $request)
    {
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
    
            // if (!$prestataire || $prestataire->role !== 'prestataire') {
            //     return response()->json([
            //         'message' => 'Accès non autorisé.',
            //     ], 403);
            // }
    
            $rendezVous = RendezVous::where('rendez_vous.prestataire_id', $prestataire->id)  // Précisez la table pour prestataire_id
            ->with(['type_rendezvous', 'client', 'disponibilite'])
            ->join('disponibilites', 'rendez_vous.disponibilite_id', '=', 'disponibilites.id')
            ->orderBy('disponibilites.jour', 'asc')  // Qualifiez aussi la colonne 'jour' de la table 'disponibilites'
            ->orderBy('disponibilites.heureDebut', 'asc')  // Qualifiez 'heureDebut' de la table 'disponibilites'
            ->select('rendez_vous.*')
            ->get();
        
    
            if ($rendezVous->isEmpty()) {
                return response()->json([
                    'debug' => 'Aucun rendez-vous trouvé.',
                    'query_result' => $rendezVous,
                    'prestataire_id' => $prestataire->id,
                ], 404);
            }
    
            return response()->json([
                'debug' => 'Rendez-vous récupérés avec succès.',
                'rendezVous' => $rendezVous,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    
    

    //Methode de validation de rendez-vous
    public function validerRendezVous(Request $request, $rendezVousId)
    {
        
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }

        $rendezVous = RendezVous::find($rendezVousId);

        
        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        
        if ($rendezVous->prestataire_id !== $prestataire->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à valider ce rendez-vous'], 403);
        }

        
        $rendezVous->statut = 'validé';
        $rendezVous->save();

        
        $client = User::find($rendezVous->client_id);
        //Mail::to($client->email)->send(new ValiderRendezVousParPrestataire($rendezVous));
        $client->notify(new ValiderRendezVousParPrestataire($rendezVous));
    

        return response()->json([
            'message' => 'Rendez-vous validé avec succès',
            'rendezVous' => $rendezVous
        ]);
    }





    //Methode pour annuler un rendez-vous
    public function annulerRendezVous(Request $request, $rendezVousId)
    {
        try {
            // Authentifier le prestataire via le token JWT
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
    
        // Récupérer le rendez-vous via son ID
        $rendezVous = RendezVous::find($rendezVousId);
    
        // Vérifier si le rendez-vous existe
        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }
    
        // Vérifier si le prestataire connecté est bien celui qui a créé ce rendez-vous
        if ($rendezVous->prestataire_id !== $prestataire->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à annuler ce rendez-vous'], 403);
        }
    
        // Mettre à jour le statut du rendez-vous pour le marquer comme annulé
        $rendezVous->statut = 'annulé';
        $rendezVous->save();
    
        // Récupérer les informations du client associé au rendez-vous
        $client = User::find($rendezVous->client_id);
    
        
        //Mail::to($client->email)->send(new AnnulerRendezVousParPrestataire($rendezVous));
        $client->notify(new AnnulerRendezVousParPrestataire($rendezVous));
    
    
        // Retourner la réponse avec le rendez-vous annulé
        return response()->json([
            'message' => 'Rendez-vous annulé avec succès',
            'rendezVous' => $rendezVous
        ]);
    }
    
}

