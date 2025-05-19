<?php

namespace App\Http\Controllers;

use App\Models\Disponibilite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\ValidationException;

class DisponibiliteController extends Controller
{
    /**
     * Méthode privée pour l'authentification du token JWT
     *
     * @return User
     * @throws \Tymon\JWTAuth\Exceptions\JWTException
     */
    private function authenticateUser()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                throw new \Exception('Utilisateur non authentifié');
            }

            return $user;
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            throw new \Exception('Token expiré', 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            throw new \Exception('Token invalide', 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            throw new \Exception('Token manquant', 401);
        }
    }

    /**
     * Définir les disponibilités pour l'utilisateur connecté
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function definirDisponibilites(Request $request)
    {
        try {
            // Validation des données
            $validatedData = $request->validate([
                'jour' => 'required|date',
                'heureDebut' => 'required|date_format:H:i',
                'heureFin' => 'required|date_format:H:i|after:heureDebut',
                'estDisponible' => 'required|boolean',
                'dureeCreneaux' => 'sometimes|integer|min:15' // Ajout optionnel pour la durée des créneaux
            ]);

            // Authentification
            $user = $this->authenticateUser();

            // Création ou mise à jour de la disponibilité
            $disponibilite = Disponibilite::updateOrCreate(
                [
                    'date' => $validatedData['jour'],
                    'heureDebut' => $validatedData['heureDebut'],
                    'heureFin' => $validatedData['heureFin'],
                    'prestataire_id' => $user->id,
                ],
                [
                    'estDisponible' => $validatedData['estDisponible'],
                ]
            );

            // Calcul optionnel des créneaux si une durée est fournie
            $creneaux = [];
            if (isset($validatedData['dureeCreneaux'])) {
                $creneaux = $disponibilite->calculerPlagesHoraires($validatedData['dureeCreneaux']);
            }

            Log::info('Disponibilité définie', [
                'user_id' => $user->id,
                'disponibilite_id' => $disponibilite->id,
                'creneaux' => $creneaux
            ]);

            return response()->json([
                'message' => 'Disponibilités définies avec succès',
                'disponibilite' => $disponibilite,
                'creneaux' => $creneaux
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la définition des disponibilités', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Lister les disponibilités de l'utilisateur connecté
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listerDisponibilites()
    {
        try {
            // Authentification
            $user = $this->authenticateUser();

            // Récupération des disponibilités
            $disponibilites = Disponibilite::where('prestataire_id', $user->id)
                                          ->get();

            // Vérification si des disponibilités existent
            if ($disponibilites->isEmpty()) {
                return response()->json([
                    'message' => 'Aucune disponibilité trouvée'
                ], 404);
            }

            return response()->json([
                'message' => 'Disponibilités récupérées avec succès',
                'disponibilites' => $disponibilites
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des disponibilités', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Méthode pour calculer les créneaux disponibles pour une disponibilité spécifique
     *
     * @param int $disponibiliteId
     * @param int $duree
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculerCreneaux($disponibiliteId, $duree)
    {
        try {
            // Authentification
            $user = $this->authenticateUser();

            // Trouver la disponibilité
            $disponibilite = Disponibilite::findOrFail($disponibiliteId);

            // Vérifier que l'utilisateur est bien le propriétaire de la disponibilité
            if ($disponibilite->prestataire_id !== $user->id) {
                return response()->json([
                    'message' => 'Accès non autorisé'
                ], 403);
            }

            // Calculer les créneaux
            $creneaux = $disponibilite->calculerPlagesHoraires($duree);

            return response()->json([
                'message' => 'Créneaux calculés avec succès',
                'creneaux' => $creneaux
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors du calcul des créneaux', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ], $e->getCode() ?: 500);
        }
    }
}
