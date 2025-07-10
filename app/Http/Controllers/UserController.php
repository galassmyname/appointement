<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Jobs\NotifyClientAboutRendezVous;
use App\Jobs\SendReminderEmail;
use App\Models\Disponibilite;
use App\Models\Prestataire;
use App\Models\RendezVous;
use App\Models\Reservation;
use App\Models\TypeRendezVous;
use App\Models\User;
use App\Notifications\DemandeRendezVousNotification;
use App\Notifications\RendezVousAnnuleNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Mail;

use App\Mail\PrestatairePasswordMail;

class UserController extends Controller
{
    /**
     * Authentifie l'utilisateur en utilisant le token JWT
     *
     * @return \App\Models\User|null
     */
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            abort(response()->json(['message' => 'Token expiré'], 401));
        } catch (TokenInvalidException $e) {
            abort(response()->json(['message' => 'Token invalide'], 401));
        } catch (JWTException $e) {
            abort(response()->json(['message' => 'Token manquant'], 401));
        } catch (\Exception $e) {
            abort(response()->json(['message' => 'Erreur d\'authentification', 'error' => $e->getMessage()], 500));
        }
    }

    /**
     * Liste les types de rendez-vous disponibles
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listerTypeDeRV()
    {
        try {
            return response()->json(["typeRendezVous" => TypeRendezVous::all()]);
        } catch (\Exception $exception) {
            return response()->json([
                $exception->getMessage()
            ], 422);
        }
    }

    /**
     * Liste les disponibilités d'un prestataire spécifique
     *
     * @param int $prestataire_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function DisponibilitesPrestataireChoisi($prestataire_id)
    {
        try {
            // Vérifier si le prestataire existe
            $prestataire = User::find($prestataire_id);

            if (!$prestataire) {
                return response()->json(['message' => 'Prestataire non trouvé'], 404);
            }

            // Récupérer les disponibilités du prestataire avec estDisponible = 1
            $disponibilites = Disponibilite::where('prestataire_id', $prestataire_id)
                ->where('estDisponible', 1)
                ->get();

            if ($disponibilites->isEmpty()) {
                return response()->json(['message' => 'Aucune disponibilité trouvée pour ce prestataire'], 404);
            }

            // Retourner les disponibilités
            return response()->json([
                'message' => 'Disponibilités du prestataire récupérées avec succès',
                'prestataire' => [
                    'id' => $prestataire->id,
                    'nom' => $prestataire->name,
                    'email' => $prestataire->email
                ],
                'disponibilites' => $disponibilites
            ], 200);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les disponibilités de tous les prestataires
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listerDisponibilitesPrestataires()
    {
        try {
            // Récupérer les prestataires ayant au moins une disponibilité avec estDisponible = 1
            $prestatairesAvecDisponibilites = User::whereHas('disponibilites', function ($query) {
                $query->where('estDisponible', 1);
            })->get();

            if ($prestatairesAvecDisponibilites->isEmpty()) {
                return response()->json(['message' => 'Aucun prestataire avec des disponibilités trouvées'], 404);
            }

            $resultats = [];

            // Parcourir les prestataires pour structurer les données
            foreach ($prestatairesAvecDisponibilites as $prestataire) {
                // Récupérer les disponibilités avec estDisponible = 1
                $disponibilites = $prestataire->disponibilites()->where('estDisponible', 1)->get();

                // Ajouter les informations du prestataire et ses disponibilités au tableau de résultats
                $resultats[] = [
                    'prestataire' => [
                        'id' => $prestataire->id,
                        'nom' => $prestataire->name,
                        'email' => $prestataire->email
                    ],
                    'disponibilites' => $disponibilites
                ];
            }

            // Retourner les disponibilités des prestataires
            return response()->json([
                'message' => 'Disponibilités des prestataires récupérées avec succès',
                'data' => $resultats
            ], 200);
        } catch (\Exception $e) {
            // Gestion des erreurs inattendues
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite.',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }
   public function ajouterPrestataire(Request $request)
{
    \Log::info('Requête d\'ajout prestataire:', $request->all());
    
    $user = auth()->user();
    \Log::info('Utilisateur authentifié:', $user ? $user->toArray() : 'Non authentifié');

    if (!$user) {
        return response()->json(['message' => 'Non authentifié'], 401);
    }

    if ($user->role !== 'admin') {
        return response()->json(['message' => 'Accès refusé'], 403);
    }

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'telephone' => 'required|string|unique:users',
        'specialite' => 'required|string'
    ]);

    $password = Str::random(10); // Génère un mot de passe aléatoire

    $prestataire = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'telephone' => $validated['telephone'],
        'specialite' => $validated['specialite'],
        'password' => Hash::make($password),
        'role' => 'prestataire'
    ]);

    // Envoi de l'email
    Mail::to($prestataire->email)
        ->send(new PrestatairePasswordMail(
            $prestataire->name,
            $password,
            $prestataire->email
        ));

    return response()->json($prestataire, 201);
}
    /**
     * Obtient les plages horaires disponibles pour un prestataire selon la durée déterminée
     * Utilise la méthode calculerPlagesHoraires du modèle Disponibilite
     *
     * @param Request $request
     * @param int $idPrestataire
     * @return \Illuminate\Http\JsonResponse
     */
    // public function obtenirPlagesDisponibles(Request $request, $idPrestataire)
    // {
    //     // Validation des données d'entrée avec des règles supplémentaires pour la durée et la date
    //     $request->validate([
    //         'date' => 'required|date|date_format:Y-m-d', // Format de date YYYY-MM-DD
    //         'duree' => [
    //             'required',
    //             'integer',
    //             'min:15',
    //             function($attribute, $value, $fail) {
    //                 // Vérifier si la durée est un multiple de 5
    //                 if ($value % 5 !== 0) {
    //                     $fail('La durée doit être un multiple de 5 minutes.');
    //                 }
    //             }
    //         ],
    //     ]);

    //     // Vérifier si le prestataire existe
    //     $prestataire = User::find($idPrestataire);
    //     if (!$prestataire) {
    //         return response()->json(['message' => 'Prestataire non trouvé'], 404);
    //     }

    //     // Récupérer le jour de la semaine correspondant à la date
    //     $jour = strtolower(Carbon::parse($request->date)->translatedFormat('l'));

    //     // Récupérer les disponibilités du prestataire pour le jour spécifié
    //     $disponibilites = Disponibilite::where('prestataire_id', $idPrestataire)
    //         ->where('jour', $jour)
    //         ->where('estDisponible', true)
    //         ->get();

    //     // Vérifier s'il y a des disponibilités pour ce jour
    //     if ($disponibilites->isEmpty()) {
    //         return response()->json([
    //             'message' => 'Le prestataire sélectionné n\'a aucune disponibilité pour la date spécifiée.',
    //             'details' => [
    //                 'date' => $request->date,
    //                 'jour' => $jour,
    //                 'prestataire' => [
    //                     'id' => $prestataire->id,
    //                     'nom' => $prestataire->name,
    //                     'email' => $prestataire->email,
    //                     'specialite' => $prestataire->specialite,
    //                 ],
    //             ]
    //         ], 404);
    //     }

    //     $dureeReunion = $request->duree; // Durée de la réunion en minutes
    //     $plagesHoraires = []; // Tableau des plages horaires disponibles

    //     // Récupérer les réservations existantes pour la date et le prestataire
    //     $reservations = Reservation::where('prestataire_id', $idPrestataire)
    //         ->where('date', $request->date)
    //         ->get(['heureDebut', 'heureFin'])
    //         ->map(function ($reservation) {
    //             return [
    //                 'heureDebut' => strtotime($reservation->heureDebut),
    //                 'heureFin' => strtotime($reservation->heureFin),
    //             ];
    //         });

    //     // Parcourir les disponibilités et utiliser la méthode calculerPlagesHoraires du modèle
    //     foreach ($disponibilites as $disponibilite) {
    //         // Utiliser la méthode du modèle pour calculer les plages disponibles
    //         $plagesDisponibles = $disponibilite->calculerPlagesHoraires($dureeReunion);

    //         // Filtrer les plages qui sont en conflit avec les réservations existantes
    //         foreach ($plagesDisponibles as $heureDebut => $valeur) {
    //             $debutTimestamp = strtotime($heureDebut);
    //             $finTimestamp = $debutTimestamp + ($dureeReunion * 60);

    //             // Vérifier si cette plage est en conflit avec des réservations existantes
    //             $conflit = $reservations->first(function ($reservation) use ($debutTimestamp, $finTimestamp) {
    //                 return $debutTimestamp < $reservation['heureFin'] &&
    //                     $finTimestamp > $reservation['heureDebut'];
    //             });

    //             // Ajouter la plage si elle n'est pas en conflit
    //             if (!$conflit) {
    //                 $plagesHoraires[] = [
    //                     'heureDebut' => date('H:i', $debutTimestamp),
    //                     'heureFin' => date('H:i', $finTimestamp),
    //                 ];
    //             }
    //         }
    //     }

    //     // Vérifier si des plages horaires ont été calculées
    //     if (empty($plagesHoraires)) {
    //         return response()->json(['message' => 'Aucune plage horaire disponible pour la durée choisie ou en raison de conflits avec des réservations existantes.'], 404);
    //     }

    //     // Retourner les plages horaires calculées
    //     return response()->json([
    //         'disponibilite_id' => $disponibilite->id,
    //         'date' => $request->date,
    //         'jour' => $jour,
    //         'data' => $plagesHoraires
    //     ], 200);
    // }

    // public function obtenirPlagesDisponibles(Request $request, $idPrestataire)
    // {
    //     // Validation des données d'entrée avec des règles pour la date et la durée
    //     $request->validate([
    //         'date' => 'required|date|date_format:Y-m-d', // Format de date YYYY-MM-DD
    //         'duree' => [
    //             'required',
    //             'integer',
    //             'min:15',
    //             function ($attribute, $value, $fail) {
    //                 // Vérifier si la durée est un multiple de 5
    //                 if ($value % 5 !== 0) {
    //                     $fail('La durée doit être un multiple de 5 minutes.');
    //                 }
    //             }
    //         ],
    //     ]);

    //     // Vérifier si le prestataire existe
    //     $prestataire = User::find($idPrestataire);
    //     if (!$prestataire) {
    //         return response()->json(['message' => 'Prestataire non trouvé'], 404);
    //     }

    //     // Récupérer le jour de la semaine correspondant à la date
    //     $date = Carbon::parse($request->date);
    //     $dateFormatee = $date->format('Y-m-d');
    //     $jour = strtolower($date->translatedFormat('l')); // Jour de la semaine en français
    //     $dureeReunion = $request->duree; // Durée de la réunion en minutes

    //     // Récupérer les disponibilités du prestataire pour le jour spécifié
    //     $disponibilites = Disponibilite::where('prestataire_id', $idPrestataire)
    //         ->where('jour', $jour)
    //         ->where('estDisponible', true)
    //         ->get();

    //     // Vérifier s'il y a des disponibilités pour ce jour
    //     if ($disponibilites->isEmpty()) {
    //         return response()->json([
    //             'message' => 'Le prestataire n\'a aucune disponibilité pour la date spécifiée.',
    //             'details' => [
    //                 'date' => $dateFormatee,
    //                 'jour' => $jour,
    //                 'prestataire' => [
    //                     'id' => $prestataire->id,
    //                     'nom' => $prestataire->name
    //                 ],
    //             ]
    //         ], 404);
    //     }

    //     // Récupérer les réservations existantes pour la date et le prestataire
    //     $reservations = Reservation::where('prestataire_id', $idPrestataire)
    //         ->where('date', $dateFormatee)
    //         ->get(['heureDebut', 'heureFin'])
    //         ->map(function ($reservation) {
    //             return [
    //                 'heureDebut' => strtotime($reservation->heureDebut),
    //                 'heureFin' => strtotime($reservation->heureFin),
    //             ];
    //         });

    //     $plagesHoraires = []; // Tableau des plages horaires disponibles

    //     // Parcourir les disponibilités et calculer les plages horaires
    //     foreach ($disponibilites as $disponibilite) {
    //         // Utiliser la méthode du modèle pour calculer les plages disponibles
    //         $plagesDisponibles = $disponibilite->calculerPlagesHoraires($dureeReunion);

    //         // Filtrer les plages qui sont en conflit avec les réservations existantes
    //         foreach ($plagesDisponibles as $heureDebut => $valeur) {
    //             $debutTimestamp = strtotime($heureDebut);
    //             $finTimestamp = $debutTimestamp + ($dureeReunion * 60);

    //             // Vérifier si cette plage est en conflit avec des réservations existantes
    //             $conflit = $reservations->first(function ($reservation) use ($debutTimestamp, $finTimestamp) {
    //                 return $debutTimestamp < $reservation['heureFin'] &&
    //                     $finTimestamp > $reservation['heureDebut'];
    //             });

    //             // Ajouter la plage si elle n'est pas en conflit
    //             if (!$conflit) {
    //                 $plagesHoraires[] = [
    //                     'heureDebut' => date('H:i', $debutTimestamp),
    //                     'heureFin' => date('H:i', $finTimestamp),
    //                 ];
    //             }
    //         }
    //     }

    //     // Vérifier si des plages horaires ont été calculées
    //     if (empty($plagesHoraires)) {
    //         return response()->json([
    //             'message' => 'Aucune plage horaire disponible pour la date et la durée choisies.',
    //             'details' => [
    //                 'date' => $dateFormatee,
    //                 'jour' => $jour,
    //                 'duree' => $dureeReunion . ' minutes'
    //             ]
    //         ], 404);
    //     }

    //     // Retourner les plages horaires calculées
    //     return response()->json([
    //         'prestataire' => [
    //             'id' => $prestataire->id,
    //             'nom' => $prestataire->name
    //         ],
    //         'date' => $dateFormatee,
    //         'jour' => $jour,
    //         'disponibilite_id' => $disponibilites->first()->id ?? null,
    //         'duree' => $dureeReunion . ' minutes',
    //         'plages' => $plagesHoraires
    //     ], 200);
    // }

    public function obtenirPlagesDisponibles(Request $request, $idPrestataire)
    {
        // 1. Validation des données d'entrée
        $request->validate([
            'date' => 'required|date|date_format:Y-m-d',
            'duree' => [
                'required',
                'integer',
                'min:15',
                function ($attribute, $value, $fail) {
                    if ($value % 5 !== 0) {
                        $fail('La durée doit être un multiple de 5 minutes.');
                    }
                }
            ],
        ]);

        // 2. Vérification de l'existence du prestataire
        $prestataire = User::find($idPrestataire);
        if (!$prestataire) {
            return response()->json(['message' => 'Prestataire non trouvé.'], 404);
        }

        // 3. Formatage de la date
        try {
            $date = Carbon::parse($request->input('date'))->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Date invalide.'], 422);
        }

        $duree = (int) $request->input('duree');

        // 4. Récupération des disponibilités pour la date
        $disponibilites = Disponibilite::where('prestataire_id', $idPrestataire)
            ->whereDate('date', $date)
            ->where('estDisponible', true)
            ->get();

        if ($disponibilites->isEmpty()) {
            return response()->json([
                'message' => "Aucune disponibilité pour la date spécifiée.",
                'details' => [
                    'date' => $date,
                    'prestataire' => [
                        'id' => $prestataire->id,
                        'nom' => $prestataire->name,
                    ],
                ],
            ], 404);
        }

        // 5. Récupération des réservations existantes
        $reservations = Reservation::where('prestataire_id', $idPrestataire)
            ->where('date', $date)
            ->get(['heureDebut', 'heureFin'])
            ->map(fn($r) => [
                'heureDebut' => strtotime($r->heureDebut),
                'heureFin' => strtotime($r->heureFin),
            ]);

        $plagesHoraires = [];

        // 6. Calcul des plages disponibles (en évitant les conflits)
        foreach ($disponibilites as $dispo) {
            if (!method_exists($dispo, 'calculerPlagesHoraires')) {
                continue;
            }

            $plages = $dispo->calculerPlagesHoraires($duree);

            foreach ($plages as $heureDebut => $val) {
                $start = strtotime($heureDebut);
                $end = $start + ($duree * 60);

                $enConflit = $reservations->contains(
                    fn($r) =>
                    $start < $r['heureFin'] && $end > $r['heureDebut']
                );

                if (!$enConflit) {
                    $plagesHoraires[] = [
                        'heureDebut' => date('H:i', $start),
                        'heureFin' => date('H:i', $end),
                    ];
                }
            }
        }

        // 7. Si aucune plage n’est trouvée
        if (empty($plagesHoraires)) {
            return response()->json([
                'message' => 'Aucune plage horaire disponible pour cette date et durée.',
                'details' => [
                    'date' => $date,
                    'duree' => "$duree minutes",
                ]
            ], 404);
        }

        // 8. Réponse finale
        return response()->json([
            'prestataire' => [
                'id' => $prestataire->id,
                'nom' => $prestataire->name
            ],
            'date' => $date,
            'duree' => "$duree minutes",
            'disponibilite_id' => $disponibilites->first()->id ?? null,
            'plages' => $plagesHoraires
        ]);
    }



    /**
     * Demande un rendez-vous
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function demanderRendezVous(Request $request)
    {
        // Validation des données d'entrée
        $request->validate([
            'disponibilite_id' => 'required|exists:disponibilites,id',
            'type_rendezvous_id' => 'required|exists:type_rendez_vous,id',
            'duree' => 'required|integer|min:15',
            'intervalPlanification' => 'required|in:disponible_maintenant,dans_une_fourchette',
            'delaiPreReservation' => 'required|integer|min:0',
            'dureeAvantAnnulation' => 'required|integer|min:0',
            'heureDebut' => 'required|date_format:H:i',
            'nombre_jours' => 'required_if:intervalPlanification,disponible_maintenant|integer|min:1',
            'date_debut' => 'required_if:intervalPlanification,dans_une_fourchette|date',
            'date_fin' => 'required_if:intervalPlanification,dans_une_fourchette|date|after_or_equal:date_debut',
        ]);

        DB::beginTransaction(); // Démarrer une transaction

        try {
            // Authentification de l'utilisateur
            $client = $this->authenticateUser();

            // Récupérer la disponibilité
            $disponibilite = Disponibilite::with('prestataire')->find($request->disponibilite_id);
            if (!$disponibilite || !$disponibilite->estDisponible) {
                return response()->json(['message' => 'La disponibilité sélectionnée est indisponible.'], 404);
            }

            // Gestion de l'intervalPlanification
            if ($request->intervalPlanification === 'disponible_maintenant') {
                $dateDebut = now();
                $dateFin = now()->addDays($request->nombre_jours);
            } else { // dans_une_fourchette
                $dateDebut = Carbon::parse($request->date_debut);
                $dateFin = Carbon::parse($request->date_fin);
            }

            // Récupérer les plages horaires disponibles en utilisant la méthode du modèle
            $plagesDisponibles = $disponibilite->calculerPlagesHoraires($request->duree);

            // Vérifier si l'heure de début demandée est disponible
            if (!array_key_exists($request->heureDebut, $plagesDisponibles)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'L\'heure de début demandée n\'est pas disponible.',
                    'plages_disponibles' => $plagesDisponibles
                ], 400);
            }

            // Calcul de l'heure de fin à partir de l'heure de début et de la durée
            $heureDebutDemandee = strtotime($request->heureDebut);
            $heureFinDemandee = $heureDebutDemandee + ($request->duree * 60);

            // Conversion du jour de la semaine en date réelle
            $jour = $disponibilite->jour;
            $joursSemaine = [
                'lundi' => 1,
                'mardi' => 2,
                'mercredi' => 3,
                'jeudi' => 4,
                'vendredi' => 5,
                'samedi' => 6,
                'dimanche' => 0
            ];

            if (isset($joursSemaine[strtolower($jour)])) {
                $aujourdHui = Carbon::now();
                $jourCible = $joursSemaine[strtolower($jour)];
                $jourActuel = $aujourdHui->dayOfWeek;
                $joursAjout = ($jourCible - $jourActuel + 7) % 7;
                $dateReelle = $aujourdHui->copy()->addDays($joursAjout)->toDateString();
            } else {
                $dateReelle = date('Y-m-d');
            }

            // Vérifier les conflits avec les dates réelles
            $conflit = Reservation::where('prestataire_id', $disponibilite->prestataire_id)
                ->where('date', $dateReelle)
                ->where(function ($query) use ($heureDebutDemandee, $heureFinDemandee) {
                    $query->whereBetween('heureDebut', [date('H:i', $heureDebutDemandee), date('H:i', $heureFinDemandee)])
                        ->orWhereBetween('heureFin', [date('H:i', $heureDebutDemandee), date('H:i', $heureFinDemandee)])
                        ->orWhere(function ($query) use ($heureDebutDemandee, $heureFinDemandee) {
                            $query->where('heureDebut', '<=', date('H:i', $heureDebutDemandee))
                                ->where('heureFin', '>=', date('H:i', $heureFinDemandee));
                        });
                })->exists();

            if ($conflit) {
                DB::rollBack(); // Annuler la transaction
                return response()->json([
                    'message' => 'Le créneau horaire est déjà réservé. Veuillez choisir un autre créneau.',
                ], 409);
            }

            // Vérifier également si un rendez-vous identique existe déjà
            $rendezVousExistant = RendezVous::where('disponibilite_id', $request->disponibilite_id)
                ->where('client_id', $client->id)
                ->where('prestataire_id', $disponibilite->prestataire_id)
                ->where('heureDebut', date('H:i', $heureDebutDemandee))
                ->where('heureFin', date('H:i', $heureFinDemandee))
                ->exists();

            if ($rendezVousExistant) {
                DB::rollBack(); // Annuler la transaction
                return response()->json([
                    'message' => 'Vous avez déjà demandé un rendez-vous identique.',
                ], 409);
            }

            // Création de la réservation
            $reservation = Reservation::create([
                'date' => $dateReelle,
                'heureDebut' => date('H:i', $heureDebutDemandee),
                'heureFin' => date('H:i', $heureFinDemandee),
                'prestataire_id' => $disponibilite->prestataire_id,
                'client_id' => $client->id,
            ]);

            // S'assurer que la réservation a bien été créée
            if (!$reservation || !$reservation->id) {
                DB::rollBack(); // Annuler la transaction
                return response()->json([
                    'message' => 'Erreur lors de la création de la réservation.',
                ], 500);
            }

            $intervalPlanificationMap = [
                'disponible_maintenant' => 1,
                'dans_une_fourchette' => 2
            ];

            $intervalPlanificationValue = $intervalPlanificationMap[$request->intervalPlanification] ?? null;

            if ($intervalPlanificationValue === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Valeur d\'intervalPlanification invalide'
                ], 400);
            }

            // Création du rendez-vous
            $rendezVous = RendezVous::create([
                'duree' => $request->duree,
                'delaiPreReservation' => $request->delaiPreReservation,
                'intervalPlanification' => $intervalPlanificationValue,
                'dureeAvantAnnulation' => $request->dureeAvantAnnulation,
                'disponibilite_id' => $disponibilite->id,
                'type_rendezvous_id' => $request->type_rendezvous_id,
                'client_id' => $client->id,
                'prestataire_id' => $disponibilite->prestataire_id,
                'jour' => $disponibilite->jour,
                'heureDebut' => $request->heureDebut,
                'heureFin' => date('H:i', $heureFinDemandee),
                'statut' => 'en attente',
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
            ]);

            // Notification au prestataire
            $disponibilite->prestataire->notify(new DemandeRendezVousNotification($rendezVous));

            if ($rendezVous->statut === 'valide') {
                $heureDebut = Carbon::parse($rendezVous->heureDebut, $rendezVous->jour);
                $delaiAvantNotification = $heureDebut->subMinutes(60);

                // Planifier l'envoi de l'email
                NotifyClientAboutRendezVous::dispatch($rendezVous)->delay($delaiAvantNotification);
            }

            DB::commit(); // Confirmer la transaction

            return response()->json([
                'message' => 'Votre demande de rendez-vous a été enregistrée avec succès.',
                'reservation' => $reservation,
                'rendezVous' => $rendezVous
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Annuler la transaction en cas d'erreur
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite lors de la demande de rendez-vous.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste les rendez-vous de l'utilisateur connecté
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listerRendezVous()
    {
        try {
            // Authentification de l'utilisateur
            $client = $this->authenticateUser();

            // Récupérer les rendez-vous de l'utilisateur connecté
            $rendezVous = RendezVous::where('client_id', $client->id)
                ->with(['type_rendezvous', 'prestataire', 'disponibilite']) // Charger les relations nécessaires
                ->join('disponibilites', 'rendez_vous.disponibilite_id', '=', 'disponibilites.id') // Joindre la table disponibilités
                ->orderBy('disponibilites.date', 'asc') // Trier par le jour depuis disponibilités
                ->orderBy('heureDebut', 'asc') // Trier aussi par heure de début
                ->select('rendez_vous.*') // S'assurer de sélectionner uniquement les colonnes de rendez-vous
                ->get();

            // Vérifier si des rendez-vous existent
            if ($rendezVous->isEmpty()) {
                return response()->json([
                    'message' => 'Aucun rendez-vous trouvé pour l\'utilisateur connecté.',
                ], 404);
            }

            return response()->json([
                'message' => 'Liste des rendez-vous récupérée avec succès.',
                'rendezVous' => $rendezVous,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite lors de la récupération des rendez-vous.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Annule un rendez-vous
     *
     * @param Request $request
     * @param int $rendezVousId
     * @return \Illuminate\Http\JsonResponse
     */
    public function annulerRendezVous(Request $request, $rendezVousId)
    {
        try {
            // Authentification de l'utilisateur
            $client = $this->authenticateUser();

            $rendezVous = RendezVous::find($rendezVousId);

            if (!$rendezVous) {
                return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
            }

            // Vérifier si l'utilisateur connecté est soit le client, soit le prestataire du rendez-vous
            if ($rendezVous->client_id !== $client->id && $rendezVous->prestataire_id !== $client->id) {
                return response()->json(['message' => 'Vous n\'êtes pas autorisé à annuler ce rendez-vous'], 403);
            }

            $rendezVous->statut = 'annulé';
            $rendezVous->save();

            // Mettre à jour la disponibilité
            $disponibilite = Disponibilite::find($rendezVous->disponibilite_id);
            if ($disponibilite) {
                $disponibilite->estDisponible = true;
                $disponibilite->save();
            }

            // Notifier le prestataire de l'annulation
            $prestataire = User::find($rendezVous->prestataire_id);
            if ($prestataire) {
                try {
                    $prestataire->notify(new RendezVousAnnuleNotification($rendezVous));
                } catch (\Exception $e) {
                    \Log::error('Erreur lors de l\'envoi de la notification: ' . $e->getMessage());
                }
            }

            return response()->json([
                'message' => 'Rendez-vous annulé avec succès',
                'rendezVous' => $rendezVous
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite lors de l\'annulation du rendez-vous.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
