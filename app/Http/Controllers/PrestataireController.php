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

    /**
     * Vérifie l'authentification du prestataire
     *
     * @return mixed Prestataire authentifié ou réponse d'erreur
     */
    private function authenticatePrestataire()
    {
        try {
            $prestataire = JWTAuth::parseToken()->authenticate();
            if (!$prestataire) {
                return response()->json(['message' => 'Utilisateur non authentifié'], 401);
            }
            return $prestataire;
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token expiré'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalide'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token manquant'], 401);
        }
    }


    /**
     * Méthode pour permettre au prestataire de définir ses disponibilités
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function definirDisponibilites(Request $request)
    {
        $request['date'] = strtolower($request['date']);
        $request->validate([
            'date' => 'required|date',
            'heureDebut' => 'required|date_format:H:i',
            'heureFin' => 'required|date_format:H:i|after:heureDebut',
        ]);

        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        // Vérification des chevauchements
        $existingDisponibilites = Disponibilite::where('prestataire_id', $prestataire->id)
            ->where('date', $request->date)
            ->where(function ($query) use ($request) {
                $query->whereBetween('heureDebut', [$request->heureDebut, $request->heureFin])
                    ->orWhereBetween('heureFin', [$request->heureDebut, $request->heureFin])
                    ->orWhere(function ($query) use ($request) {
                        $query->where('heureDebut', '<=', $request->heureDebut)
                            ->where('heureFin', '>=', $request->heureFin);
                    });
            })
            ->exists();

        if ($existingDisponibilites) {
            return response()->json([
                'message' => 'Les créneaux horaires se chevauchent avec une disponibilité existante.',
            ], 422);
        }

        // Créer une nouvelle disponibilité
        try {
            $disponibilite = Disponibilite::create([
                'date' => $request->date,
                'prestataire_id' => $prestataire->id,
                'heureDebut' => $request->heureDebut,
                'heureFin' => $request->heureFin,
                'estDisponible' => true,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Disponibilité pour ce date ' . $request->jour . ' définie avec succès',
            'disponibilite' => $disponibilite
        ]);
    }

    /**
     * Récupérer une disponibilité d'un prestataire spécifique en fonction du jour
     *
     * @param string $jour
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDisponibilite($date)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        $disponibilites = Disponibilite::where('date', strtolower($date))
            ->where('prestataire_id', $prestataire->id)
            ->get();

        if ($disponibilites->isEmpty()) {
            return response()->json(['message' => 'Aucune disponibilité trouvée pour ce date'], 404);
        }

        return response()->json(['disponibilites' => $disponibilites]);
    }

    /**
     * Méthode pour récupérer une disponibilité par son ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDisponibiliteById($id)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        // Récupération de la disponibilité par ID
        $id = (int)$id;
        $disponibilite = Disponibilite::where('id', $id)
            ->where('prestataire_id', $prestataire->id)
            ->first();

        if (!$disponibilite) {
            return response()->json(['message' => 'Disponibilité introuvable ou ne vous appartient pas'], 404);
        }

        // Retour de la disponibilité
        return response()->json(['disponibilite' => $disponibilite]);
    }

    /**
     * Methode pour lister les creneaux horaires du prestataire
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listerDisponibilites(Request $request)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
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

    /**
     * Methode pour modifier une disponibilite
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function modifierDisponibilite(Request $request, $id)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        try {
            $disponibilite = Disponibilite::find($id);

            if (!$disponibilite) {
                return response()->json(['message' => 'Disponibilité introuvable'], 404);
            }

            if ($disponibilite->prestataire_id != $prestataire->id) {
                return response()->json(['message' => 'Vous ne pouvez modifier que vos propres disponibilités'], 403);
            }

            // Validation des données si elles sont fournies
            if ($request->has('jour') || $request->has('heureDebut') || $request->has('heureFin')) {
                $validatedData = $request->validate([
                    'date' => 'date|date',
                    'heureDebut' => 'sometimes|date_format:H:i',
                    'heureFin' => 'sometimes|date_format:H:i|after:heureDebut',
                    'estDisponible' => 'sometimes|boolean',
                ]);

                // Si le jour ou les heures changent, vérifier les chevauchements
                if ($request->has('date') || $request->has('heureDebut') || $request->has('heureFin')) {
                    $date = $request->date ?? $disponibilite->date;
                    $heureDebut = $request->heureDebut ?? $disponibilite->heureDebut;
                    $heureFin = $request->heureFin ?? $disponibilite->heureFin;

                    $existingDisponibilites = Disponibilite::where('prestataire_id', $prestataire->id)
                        ->where('date', $date)
                        ->where('id', '!=', $id) // Exclure la disponibilité actuelle
                        ->where(function ($query) use ($heureDebut, $heureFin) {
                            $query->whereBetween('heureDebut', [$heureDebut, $heureFin])
                                ->orWhereBetween('heureFin', [$heureDebut, $heureFin])
                                ->orWhere(function ($query) use ($heureDebut, $heureFin) {
                                    $query->where('heureDebut', '<=', $heureDebut)
                                        ->where('heureFin', '>=', $heureFin);
                                });
                        })
                        ->exists();

                    if ($existingDisponibilites) {
                        return response()->json([
                            'message' => 'Les créneaux horaires se chevauchent avec une disponibilité existante.',
                        ], 422);
                    }
                }
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

    /**
     * Methode pour supprimer un creneau horaire
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function supprimerDisponibilite($id)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        try {
            $disponibilite = Disponibilite::find($id);

            if (!$disponibilite) {
                return response()->json(['message' => 'Disponibilité introuvable'], 404);
            }

            if ($disponibilite->prestataire_id != $prestataire->id) {
                return response()->json(['message' => 'Vous ne pouvez supprimer que vos propres disponibilités'], 403);
            }

            // Vérifier s'il y a des rendez-vous pour cette disponibilité
            $rendezVousCount = RendezVous::where('disponibilite_id', $id)
                ->whereIn('statut', ['en_attente', 'validé'])
                ->count();

            if ($rendezVousCount > 0) {
                return response()->json([
                    'message' => 'Impossible de supprimer cette disponibilité car des rendez-vous y sont associés',
                    'rendez_vous_count' => $rendezVousCount
                ], 422);
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

    /**
     * Liste les rendez-vous du prestataire
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listerRendezVousPrestataire(Request $request)
    {
        try {
            $prestataire = $this->authenticatePrestataire();
            if (!is_object($prestataire) || isset($prestataire->original)) {
                return $prestataire;
            }

            // Paramètres de filtrage optionnels
            $status = $request->query('status');
            $dateDebut = $request->query('date_debut');
            $dateFin = $request->query('date_fin');

            // Construction de la requête
            $query = RendezVous::with([
                'type_rendezvous',
                'client',
                'disponibilite'
            ])->where('prestataire_id', $prestataire->id);

            // Appliquer les filtres si fournis
            if ($status) {
                $query->where('statut', $status);
            }

            if ($dateDebut) {
                $query->whereHas('disponibilite', function ($q) use ($dateDebut) {
                    $q->where('date', '>=', $dateDebut);
                });
            }

            if ($dateFin) {
                $query->whereHas('disponibilite', function ($q) use ($dateFin) {
                    $q->where('date', '<=', $dateFin);
                });
            }

            // Exécuter la requête
            $rendezVous = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'message' => 'Rendez-vous récupérés avec succès',
                'rendezVous' => $rendezVous
            ]);
        } catch (\Exception $e) {
            // Journalisation de l'erreur complète
            Log::error('Erreur lors de la récupération des rendez-vous: ' . $e->getMessage());

            return response()->json([
                'message' => 'Une erreur est survenue',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }

    /**
     * Méthode de validation de rendez-vous
     *
     * @param Request $request
     * @param int $rendezVousId
     * @return \Illuminate\Http\JsonResponse
     */
    public function validerRendezVous(Request $request, $rendezVousId)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
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
        $client->notify(new ValiderRendezVousParPrestataire($rendezVous));

        return response()->json([
            'message' => 'Rendez-vous validé avec succès',
            'rendezVous' => $rendezVous
        ]);
    }

    /**
     * Méthode pour annuler un rendez-vous
     *
     * @param Request $request
     * @param int $rendezVousId
     * @return \Illuminate\Http\JsonResponse
     */
    public function annulerRendezVous(Request $request, $rendezVousId)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        // Récupérer le rendez-vous via son ID
        $rendezVous = RendezVous::find($rendezVousId);

        // Vérifier si le rendez-vous existe
        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        // Vérifier si le prestataire connecté est bien celui qui a ce rendez-vous
        if ($rendezVous->prestataire_id !== $prestataire->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à annuler ce rendez-vous'], 403);
        }

        // Vérifier si le rendez-vous est déjà annulé
        if (in_array($rendezVous->statut, ['annulé', 'annulé_urgence'])) {
            return response()->json(['message' => 'Ce rendez-vous est déjà annulé'], 422);
        }

        if ($rendezVous->statut === 'validé') {
            // Vérifier si 48h sont écoulées depuis la validation
            $dateValidation = new \DateTime($rendezVous->updated_at);
            $dateActuelle = new \DateTime();
            $interval = $dateValidation->diff($dateActuelle);
            $heuresEcoulees = $interval->days * 24 + $interval->h;

            if ($heuresEcoulees < 48) {
                $heuresRestantes = 48 - $heuresEcoulees;

                return response()->json([
                    'message' => "Vous ne pouvez pas annuler un rendez-vous dans les 48h suivant sa validation. Il reste encore $heuresRestantes heure(s). Utilisez l'annulation d'urgence si nécessaire.",
                ], 403);
            }
        }

        // Mettre à jour le statut du rendez-vous pour le marquer comme annulé
        $rendezVous->statut = 'annulé';
        $rendezVous->save();

        // Récupérer les informations du client associé au rendez-vous
        $raison = $request->input('raison'); // Récupération de la raison depuis le frontend
        $client = User::find($rendezVous->client_id);
        $client->notify(new AnnulerRendezVousParPrestataire($rendezVous, $raison));

        // Retourner la réponse avec le rendez-vous annulé
        return response()->json([
            'message' => 'Rendez-vous annulé avec succès',
            'rendezVous' => $rendezVous
        ]);
    }

    /**
     * Méthode pour annuler un rendez-vous en urgence
     *
     * @param Request $request
     * @param int $rendezVousId
     * @return \Illuminate\Http\JsonResponse
     */
    public function annulationUrgence(Request $request, $rendezVousId)
    {
        // dd("Bonjour");
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        // Récupérer le rendez-vous via son ID
        $rendezVous = RendezVous::find($rendezVousId);

        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        // Vérifier l'appartenance
        if ($rendezVous->prestataire_id !== $prestataire->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à annuler ce rendez-vous'], 403);
        }

        // Vérifier si le rendez-vous est déjà annulé
        if (in_array($rendezVous->statut, ['annulé', 'annulé_urgence'])) {
            return response()->json(['message' => 'Ce rendez-vous est déjà annulé.'], 400);
        }

        // Vérifier le nombre d'annulations d'urgence cette semaine
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        $annulationsUrgence = RendezVous::where('prestataire_id', $prestataire->id)
            ->where('statut', 'annulé_urgence')
            ->whereBetween('updated_at', [$debutSemaine, $finSemaine])
            ->count();

        if ($annulationsUrgence >= 1) {
            return response()->json([
                'message' => 'Vous avez déjà utilisé votre annulation d\'urgence cette semaine.',
                'limite_atteinte' => true,
                'annulations_restantes' => 0,
            ], 403);
        }


        // Récupérer la raison de l'annulation urgente envoyée depuis le frontend
        $raison = $request->input('raison');

        // dd($raison);

        // Effectuer l'annulation d'urgence
        $rendezVous->statut = 'annulé_urgence';
        $rendezVous->save();

        // Notifier le client
        $client = User::find($rendezVous->client_id);
        $client->notify(new AnnulerRendezVousParPrestataire($rendezVous, $raison));

        return response()->json([
            'message' => 'Rendez-vous annulé en urgence avec succès.',
            'annulations_restantes' => 0,
            'rendezVous' => $rendezVous
        ]);
    }

    /**
     * Vérifie le nombre d'annulations d'urgence restantes pour le prestataire
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifierAnnulationsRestantes(Request $request)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        try {
            // Début et fin de la semaine en cours (lundi à dimanche)
            $debutSemaine = now()->startOfWeek();
            $finSemaine = now()->endOfWeek();

            // Compter les annulations d'urgence cette semaine
            $annulationsUrgence = RendezVous::where('prestataire_id', $prestataire->id)
                ->where('statut', 'annulé_urgence')
                ->whereBetween('updated_at', [$debutSemaine, $finSemaine])
                ->count();

            // Une seule annulation autorisée par semaine
            $limiteHebdo = 1;
            $annulationsRestantes = max(0, $limiteHebdo - $annulationsUrgence);
            $peutAnnuler = $annulationsRestantes > 0;

            return response()->json([
                'peut_annuler_urgence' => $peutAnnuler,
                'annulations_cette_semaine' => $annulationsUrgence,
                'annulations_restantes' => $annulationsRestantes,
                'debut_semaine' => $debutSemaine->format('Y-m-d'),
                'fin_semaine' => $finSemaine->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => config('app.debug') ? $e->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }


    /**
     * La méthode pour afficher les détails d'un rendez-vous par son ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showRendezVous($id)
    {
        $prestataire = $this->authenticatePrestataire();
        if (!is_object($prestataire) || isset($prestataire->original)) {
            return $prestataire;
        }

        try {
            // Récupérer le rendez-vous avec les informations liées (type, client, disponibilité)
            $rendezVous = RendezVous::where('rendez_vous.prestataire_id', $prestataire->id)
                ->where('rendez_vous.id', $id)
                ->with(['type_rendezvous', 'client', 'disponibilite', 'prestataire'])
                ->first();

            // Vérifier si le rendez-vous existe
            if (!$rendezVous) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Rendez-vous non trouvé ou vous n\'êtes pas autorisé à accéder à ce rendez-vous.'
                ], 404);
            }

            // Retourner les détails du rendez-vous
            return response()->json([
                'status' => 'success',
                'data' => $rendezVous
            ], 200);
        } catch (\Exception $e) {
            // En cas d'erreur, retourner une réponse avec le message d'erreur
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
