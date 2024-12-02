<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
    // Inscription
    public function register(Request $request)
    {
        try {
            // Validation des champs avec des messages personnalisés
            $request->validate(
                [
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255|unique:users|regex:/@gmail\.com$/',
                    'telephone' => 'required|string|max:20|unique:users',
                    'password' => 'required|string|min:6|confirmed',
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
                    'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
                ]
            );
    
            // Création de l'utilisateur
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => Hash::make($request->password),
            ]);
    
            // Génération du token JWT
            $token = JWTAuth::fromUser($user);
    
            // Retourner une réponse JSON avec le token
            return response()->json([
                'message' => 'Inscription réussie',
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Renvoyer les erreurs de validation
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Renvoyer une erreur générale
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    


    // Connexion
    public function login(Request $request)
    {
        try {
            // Validation des informations de connexion avec des messages personnalisés
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ], [
                'email.required' => 'Le champ email est obligatoire.',
                'email.email' => 'L\'adresse email fournie est invalide.',
                'password.required' => 'Le champ mot de passe est obligatoire.',
            ]);
    
            // Vérification des informations de connexion (tentative de login avec JWT)
            // if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
            //     return response()->json(['message' => 'Email ou mot de passe incorrect'], 401);
            // }

            if (!$token = auth('api')->attempt($request->only('email', 'password'))) {
                return response()->json(['message' => 'Email ou mot de passe incorrect'], 401);
            }
            
    
            // Si la connexion réussit, retourner le token JWT
            return response()->json([
                'message' => 'Connexion réussie avec succès',
                'token' => $token
            ]);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Renvoyer les erreurs de validation
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Renvoyer une erreur générale
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    


    public function me()
    {
        try {
            // Récupérer le token depuis l'en-tête Authorization
            $token = JWTAuth::getToken();
    
            if (!$token) {
                return response()->json(['error' => 'Token non fourni'], 401);
            }
    
            // Authentifier l'utilisateur à partir du token
            $user = JWTAuth::authenticate($token);
    
            if (!$user) {
                return response()->json(['message' => 'Utilisateur non trouvé'], 404);
            }
    
            // Retourner les informations de l'utilisateur avec le token
            return response()->json([
                'message' => 'Utilisateur connecté récupéré avec succès',
                'user' => $user,
                'token' => $token, // Inclure le token dans la réponse
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Le token a expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token absent'], 401);
        }
    }
    
    

    

    // Déconnexion
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Vous etes maintenent deconnecter']);
    }

            // Profil utilisateur
            public function userProfile()
            {
                // Récupérer tous les utilisateurs de la table 'users'
                $users = User::all();
            
                // Retourner la liste des utilisateurs (par exemple, dans une vue ou en JSON)
                return response()->json($users);
            }
            
}
