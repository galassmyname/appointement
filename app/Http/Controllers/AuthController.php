<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
    // La methode pour l'inscription
    public function register(Request $request)
    {
        try {
            // Validation des champs avec des messages personnalisés
            $request->validate(
                [
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users|regex:/@gmail\.com$/',
                    'telephone' => 'required|string|max:20|unique:users',
                    'password' => 'required|string|min:6',
                ],
                [
                    'name.required' => 'Le champ nom est obligatoire.',
                    'name.max' => 'Le nom ne doit pas dépasser 255 caractères.',
                    'email.required' => 'Le champ email est obligatoire.',
                    'email.email' => 'Le format de l\'adresse email est invalide.',
                    'email.regex' => 'Le format de l\'adresse email est incorrect.',
                    'email.unique' => 'Cette adresse email est déjà utilisée.',
                    'telephone.required' => 'Le champ téléphone est obligatoire.',
                    'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
                    'password.required' => 'Le champ mot de passe est obligatoire.',
                    'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
                ]
            );
            // Vérifier si l'email existe dans la table `prestataires`
            $existsEmailInPrestataires = \App\Models\Prestataire::where('email', $request->email)->exists();
            $existsTelInPrestataires = \App\Models\Prestataire::where('telephone', $request->telephone)->exists();
            if($existsEmailInPrestataires && $existsTelInPrestataires) {
                return response()->json(['errors' =>
                    ["Cet email est déjà utilisé par un utilisateur.", "Ce telephone est déjà utilisé par un utilisateur."]],
                    422);
            }
            if($existsEmailInPrestataires){
                    return response()->json(['errors' => ["Cet email est déjà utilisé par un utilisateur."] ]
                        , 422);
                }
            if($existsTelInPrestataires){
                return response()->json(['errors' => ["Ce telephone est déjà utilisé par un utilisateur."] ]
                    , 422);
            }
            // Création de l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => Hash::make($request->password),
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'Inscription réussie',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    // CMethode pour la connexion
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'Le champ email est obligatoire.',
                'email.email' => 'L\'adresse email fournie est invalide.',
                'password.required' => 'Le champ mot de passe est obligatoire.',
            ]);

            // Vérifiez si l'utilisateur est un "User" ou un "Prestataire"
            $user = \App\Models\Prestataire::where('email', $request->email)->first();
            if (!$user) {
                $user = \App\Models\User::where('email', $request->email)->first();
            }

            // Si aucun utilisateur trouvé, retournez une erreur
            if (!$user) {
                return response()->json(['message' => 'Email ou mot de passe incorrect'], 401);
            }

            // Authentifier l'utilisateur selon le modèle trouvé
            $guard = $user instanceof \App\Models\Prestataire ? 'prestataire' : 'api';
//            return response()->json([
//                'guard' => $guard,
//            ]);


            if (!$token = auth($guard)->attempt($request->only('email', 'password'))) {
                return response()->json(['message' => 'Email ou mot de passe incorrect'], 401);
            }



            return response()->json([
                'message' => 'Connexion réussie avec succès',
                'token' => $token,

                'user' => auth($guard)->user(),

            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    // La  methode pour le refresh token d'acces
    public function refreshToken()
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['error' => 'Token non fourni'], 401);
            }

            // Rafraîchir le token
            $newToken = JWTAuth::refresh($token);

            // Créer un cookie sécurisé pour le refresh token
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
                'token' => $newToken,
            ])->cookie($cookie);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Le token a expiré et ne peut pas être rafraîchi'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Erreur lors du rafraîchissement du token'], 500);
        }
    }



    public function me()
    {
        try {

            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json(['error' => 'Token non fourni'], 401);
            }


            $user = JWTAuth::authenticate($token);

            if (!$user) {
                return response()->json(['message' => 'Utilisateur non trouvé'], 404);
            }


            return response()->json([
                'message' => 'Utilisateur connecté récupéré avec succès',
                'user' => $user,
                'token' => $token,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Le token a expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent'], 401);
        }
    }





    // Déconnexion (on doit supprimer le token apres la deconnexion)
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json(['message' => 'Aucun token trouvé'], 400);
            }
            JWTAuth::invalidate($token);


            // Supprimer le cookie du refresh token
            $cookie = cookie('refresh_token', '', -1);

            return response()->json(['message' => 'Déconnexion réussie'])->cookie($cookie);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    // Profil utilisateur(cette fonction devrait afficher le profil de l'utilisateur connecte ou dans le cas contraire c'est seulement l'admin qui peut voir tous)
    public function userProfile()
    {

        $users = User::all();


        return response()->json($users);
     }

}
