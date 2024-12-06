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
             // Gestion des erreurs inattendues
             return response()->json([
                 'status' => 'error',
                 'message' => 'Une erreur inattendue s\'est produite.',
                 'error' => $e->getMessage()
             ], 500);
         }
     }
     




     
    // Connexion d'un prestataire
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('prestataire')->attempt($credentials)) {
            return response()->json(['error' => 'Informations d identification non valides'], 401);
        }

        return response()->json([
            'message' => 'Connexion réussie avec succès',
            'token' => $token
        ]);
    }

    // Déconnexion d'un prestataire
    public function logout()
    {
        Auth::guard('prestataire')->logout();
        return response()->json(['message' => 'Vous etes déconnexion']);
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
        // Validation des données
        $request->validate([
            'jour' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche', // Validation du jour de la semaine
            'heureDebut' => 'required|date_format:H:i',
            'heureFin' => 'required|date_format:H:i|after:heureDebut',
            //'estDisponible' => 'required|boolean',
        ]);
        
        try {
            // Récupérer le prestataire connecté via le token JWT
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

        // Créer ou mettre à jour la disponibilité pour un jour précis de la semaine
        try {
            $disponibilite = Disponibilite::updateOrCreate(
                [
                    'jour' => $request->jour,  // Utilisation du jour de la semaine (lundi, mardi, etc.)
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
            // Authentifier le prestataire via le token JWT
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
    
        try {
            // Récupérer toutes les disponibilités du prestataire connecté
            $disponibilites = Disponibilite::where('prestataire_id', $prestataire->id)->get();
    
            // Si aucune disponibilité n'est trouvée
            if ($disponibilites->isEmpty()) {
                return response()->json(['message' => 'Aucune disponibilité trouvée pour ce prestataire'], 404);
            }
    
            // Retourner les disponibilités du prestataire
            return response()->json([
                'message' => 'Disponibilités récupérées avec succès',
                'disponibilites' => $disponibilites
            ]);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
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
            // Authentifier le prestataire via le token JWT
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
    
        try {
            // Récupérer la disponibilité par son ID
            $disponibilite = Disponibilite::find($id);
    
            // Vérifier si la disponibilité existe
            if (!$disponibilite) {
                return response()->json(['message' => 'Disponibilité introuvable'], 404);
            }
    
            // Vérifier si la disponibilité appartient au prestataire connecté
            if ($disponibilite->prestataire_id != $prestataire->id) {
                return response()->json(['message' => 'Vous ne pouvez modifier que vos propres disponibilités'], 403);
            }
    
            // Mettre à jour la disponibilité avec les données de la requête
            $disponibilite->update($request->all());
    
            // Retourner une réponse de succès
            return response()->json([
                'message' => 'Disponibilité modifiée avec succès',
                'disponibilite' => $disponibilite
            ]);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
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
            // Authentifier le prestataire via le token JWT
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
    
        try {
            // Récupérer la disponibilité par son ID
            $disponibilite = Disponibilite::find($id);
    
            // Vérifier si la disponibilité existe
            if (!$disponibilite) {
                return response()->json(['message' => 'Disponibilité introuvable'], 404);
            }
    
            // Vérifier si la disponibilité appartient au prestataire connecté
            if ($disponibilite->prestataire_id != $prestataire->id) {
                return response()->json(['message' => 'Vous ne pouvez supprimer que vos propres disponibilités'], 403);
            }
    
            // Supprimer la disponibilité
            $disponibilite->delete();
    
            // Retourner une réponse de succès
            return response()->json(['message' => 'Disponibilité supprimée avec succès']);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
        



    //Methode pour lister les rendez-vous du prestataire connecter
    public function listerRendezVousPrestataire(Request $request)
    {
        // Authentifier l'utilisateur via JWT sans utiliser les guards
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }


        // Vérifier que l'utilisateur est un prestataire
        $prestataire = Prestataire::where('id', $prestataire->id)->first();

        if (!$prestataire) {
            return response()->json(['message' => 'Accès non autorisé. Vous devez être un prestataire pour accéder à cette ressource.'], 403);
        }

        // Récupérer le rôle du prestataire à partir de la table roles
        $role = Role::where('id', $prestataire->role_id)->first();

        if (!$role || $role->name !== 'prestataire') {
            return response()->json(['message' => 'Accès non autorisé. Vous devez être un prestataire pour accéder à cette ressource.'], 403);
        }

        // Récupérer les rendez-vous du prestataire connecté
        $rendezVous = RendezVous::where('prestataire_id', $prestataire->id)
                                ->orderBy('date', 'asc')
                                ->orderBy('heureDebut', 'asc')
                                ->get();

        // Vérifier s'il y a des rendez-vous
        if ($rendezVous->isEmpty()) {
            return response()->json(['message' => 'Aucun rendez-vous trouvé pour ce prestataire'], 404);
        }

        return response()->json([
            'message' => 'Liste des rendez-vous pour le prestataire',
            'rendezVous' => $rendezVous
        ]);
    }


    //Methode de validation de rendez-vous
    public function validerRendezVous(Request $request, $rendezVousId)
    {
        // Authentifier l'utilisateur via JWT sans utiliser les guards
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }

        // Récupérer le rendez-vous à valider
        $rendezVous = RendezVous::find($rendezVousId);

        // Vérifier si le rendez-vous existe
        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        // Vérifier si l'utilisateur connecté est le prestataire du rendez-vous
        if ($rendezVous->prestataire_id !== $prestataire->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à valider ce rendez-vous'], 403);
        }

        // Valider le rendez-vous : changer le statut à "validé"
        $rendezVous->statut = 'validé';
        $rendezVous->save();

        // Récupérer le client associé au rendez-vous
        $client = User::find($rendezVous->client_id);

        // if ($client) {
        //     // Envoyer une notification au client pour l'informer de la validation
        //     $client->notify(new RendezVousValideNotification($rendezVous));
        // }

        return response()->json([
            'message' => 'Rendez-vous validé avec succès',
            'rendezVous' => $rendezVous
        ]);
    }





    //Methode pour annuler un rendez-vous
    public function annulerRendezVous(Request $request, $rendezVousId)
    {
        // Authentifier l'utilisateur via JWT sans utiliser les guards
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }

        // Vérifier que l'utilisateur est un prestataire
        $prestataire = Prestataire::where('user_id', $user->id)->first();
        if (!$prestataire) {
            return response()->json(['message' => 'Accès non autorisé. Vous devez être un prestataire pour accéder à cette ressource.'], 403);
        }

        // Récupérer le rôle du prestataire à partir de la table roles
        $role = Role::where('id', $prestataire->role_id)->first();
        if (!$role || $role->name !== 'prestataire') {
            return response()->json(['message' => 'Accès non autorisé. Vous devez être un prestataire pour accéder à cette ressource.'], 403);
        }

        // Récupérer le rendez-vous à annuler
        $rendezVous = RendezVous::find($rendezVousId);

        // Vérifier si le rendez-vous existe
        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        // Vérifier si l'utilisateur connecté est le prestataire du rendez-vous
        if ($rendezVous->prestataire_id !== $prestataire->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à annuler ce rendez-vous'], 403);
        }

        // Annuler le rendez-vous : changer le statut à "annulé"
        $rendezVous->statut = 'annulé';
        $rendezVous->save();

        // Récupérer la disponibilité associée au rendez-vous
        $disponibilite = Disponibilite::where('date', $rendezVous->date)
                                    ->where('heureDebut', $rendezVous->heureDebut)
                                    ->first();

        // Mettre à jour la disponibilité pour la rendre à nouveau disponible
        if ($disponibilite) {
            $disponibilite->estDisponible = true;  
            $disponibilite->save();
        }

        // Récupérer le client associé au rendez-vous
        $client = User::find($rendezVous->client_id);


        return response()->json([
            'message' => 'Rendez-vous annulé avec succès',
            'rendezVous' => $rendezVous
        ]);
    }

}

