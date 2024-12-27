<?php

namespace App\Http\Controllers;


use App\Jobs\NotifyClientAboutRendezVous;
use App\Jobs\SendReminderEmail;
use App\Models\Disponibilite;
use App\Models\Prestataire;
use App\Models\RendezVous;
use App\Models\Reservation;
use App\Models\TypeRendezVous;
use App\Models\User;
use App\Notifications\DemandeRendezVousNotification; // Import de la notification
use App\Notifications\RendezVousAnnuleNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tymon\JWTAuth\Facades\JWTAuth;
//use prestataire;

class UserController extends Controller
{


    public function listerTypeDeRV()
    {
        try {
            return response()->json(["typeRendezVous" => TypeRendezVous::all()]);
        }catch (\Exception $exception){
            return response()->json([
                $exception->getMessage()
            ],422);
        }
    }
    // La methode pour lister les disponibilite d'un prestataire
    public function DisponibilitesPrestataireChoisi($prestataire_id)
    {
        try {
            // Vérifier si le prestataire existe
            $prestataire = Prestataire::find($prestataire_id);

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
                    'nom' => $prestataire->name, // Ajoutez d'autres informations utiles sur le prestataire
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


    // La methode pour lister les disponibilites de tout les prestataires
    public function listerDisponibilitesPrestataires()
    {
        try {
            // Récupérer les prestataires ayant au moins une disponibilité avec estDisponible = 1
            $prestatairesAvecDisponibilites = Prestataire::whereHas('disponibilites', function ($query) {
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
                        'nom' => $prestataire->name, // Ajoutez d'autres informations utiles sur le prestataire
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


    // La methode pour obtenir les different crenaux possible selon la duree determiner
    public function obtenirPlagesDisponibles(Request $request, $idPrestataire)
    {
        $request['jour'] = strtolower($request['jour']);
        // Validation des données d'entrée
        $request->validate([
            'jour' => 'required|string|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'duree' => 'required|integer|min:15', // Durée de la réunion en minutes
        ]);

        // Vérifier si le prestataire existe
        $prestataire = Prestataire::find($idPrestataire);
        if (!$prestataire) {
            return response()->json(['message' => 'Prestataire non trouvé'], 404);
        }

        // Récupérer les disponibilités du prestataire pour le jour spécifié
        $disponibilites = Disponibilite::where('prestataire_id', $idPrestataire)
            ->where('jour', $request->jour)
            ->where('estDisponible', true)
            ->get();

        // Vérifier s'il y a des disponibilités pour ce jour
        if ($disponibilites->isEmpty()) {
            return response()->json([
                'message' => 'Le prestataire sélectionné n\'a aucune disponibilité pour le jour spécifié.',
                'details' => [
                    'jour' => $request->jour,
                    'prestataire' => [
                        'id' => $prestataire->id,
                        'nom' => $prestataire->name,
                        'email' => $prestataire->email,
                        'specialite' => $prestataire->specialite,
                    ],
                ]
            ], 404);
        }

        $dureeReunion = $request->duree; // Durée de la réunion en minutes
        $plagesHoraires = []; // Tableau des plages horaires disponibles

        // Récupérer les réservations existantes pour le jour et le prestataire
        $reservations = Reservation::where('prestataire_id', $idPrestataire)
            ->where('jour', $request->jour)
            ->get(['heureDebut', 'heureFin'])
            ->map(function ($reservation) {
                return [
                    'heureDebut' => strtotime($reservation->heureDebut),
                    'heureFin' => strtotime($reservation->heureFin),
                ];
            });

        // Parcourir les disponibilités pour créer des plages horaires disponibles
        foreach ($disponibilites as $disponibilite) {
            $heureDebut = strtotime($disponibilite->heureDebut);
            $heureFin = strtotime($disponibilite->heureFin);

            // Générer les plages horaires de la durée demandée
            while (($heureDebut + ($dureeReunion * 60)) <= $heureFin) {
                $nouvellePlage = [
                    'heureDebut' => $heureDebut,
                    'heureFin' => $heureDebut + ($dureeReunion * 60),
                ];

                // Vérifier si cette plage est en conflit avec des réservations existantes
                $conflit = $reservations->first(function ($reservation) use ($nouvellePlage) {
                    return $nouvellePlage['heureDebut'] < $reservation['heureFin'] &&
                        $nouvellePlage['heureFin'] > $reservation['heureDebut'];
                });

                // Ajouter la plage si elle n'est pas en conflit
                if (!$conflit) {
                    $plagesHoraires[] = [
                        'heureDebut' => date('H:i', $nouvellePlage['heureDebut']),
                        'heureFin' => date('H:i', $nouvellePlage['heureFin']),
                    ];
                }

                // Passer au créneau suivant
                $heureDebut += ($dureeReunion * 60);
            }
        }

        // Vérifier si des plages horaires ont été calculées
        if (empty($plagesHoraires)) {
            return response()->json(['message' => 'Aucune plage horaire disponible pour la durée choisie ou en raison de conflits avec des réservations existantes.'], 404);
        }

        // Retourner les plages horaires calculées
        return response()->json(['data' => $plagesHoraires], 200);
    }


    // La methode pour demander une rendez_vous sur un crenaux horaire percis
    
    // public function demanderRendezVous(Request $request)
    // {
    //     // Validation des données d'entrée

    //     try {
    //         $request->validate([
    //             'disponibilite_id' => 'required|exists:disponibilites,id',
    //             'type_rendezvous_id' => 'required|exists:type_rendez_vous,id',
    //             'duree' => 'required|integer|min:15',
    //             'delaiPreReservation' => 'required|integer|min:0',
    //             'intervalPlanification' => 'required|integer|min:0',
    //             'dureeAvantAnnulation' => 'required|integer|min:0',
    //             'heureDebut' => 'required|date_format:H:i',
    //         ]);
    //         Log::info('Validation des données réussie');
    //     } catch (\Exception $e) {
    //         Log::error('Erreur de validation : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Erreur dans les données d\'entrée.',
    //             'error' => $e->getMessage(),
    //         ], 400);
    //     }
        
    //     try {
    //         $client = JWTAuth::parseToken()->authenticate();
    //         Log::info('Utilisateur authentifié', ['client_id' => $client->id]);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur d\'authentification : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Authentification échouée.',
    //             'error' => $e->getMessage(),
    //         ], 401);
    //     }
    
    //     try {
    //     $request->validate([
    //         'disponibilite_id' => 'required|exists:disponibilites,id',
    //         'type_rendezvous_id' => 'required|exists:type_rendez_vous,id',
    //         'duree' => 'required|integer|min:15',
    //         'delaiPreReservation' => 'required|integer|min:0',
    //         'intervalPlanification' => 'required|integer|min:0',
    //         'dureeAvantAnnulation' => 'required|integer|min:0',
    //         'heureDebut' => 'required|date_format:H:i',
    //     ]);

    //     try {
    //         $client = JWTAuth::parseToken()->authenticate();

    //         // Récupérer la disponibilité
    //         $disponibilite = Disponibilite::with('prestataire')->find($request->disponibilite_id);
    //         if (!$disponibilite || !$disponibilite->estDisponible) {
    //             Log::warning('Disponibilité invalide ou indisponible', ['disponibilite_id' => $request->disponibilite_id]);
    //             return response()->json(['message' => 'La disponibilité sélectionnée est indisponible.'], 404);
    //         }
    //         Log::info('Disponibilité récupérée', ['disponibilite_id' => $disponibilite->id]);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur lors de la récupération de la disponibilité : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Erreur lors de la récupération de la disponibilité.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    
    //     try {
    //         // Vérification de la durée demandée
    //         $heureDebut = strtotime($disponibilite->heureDebut);
    //         $heureFin = strtotime($disponibilite->heureFin);
    //         $dureeDisponibilite = ($heureFin - $heureDebut) / 60;

    //         if ($request->duree > $dureeDisponibilite) {
    //             Log::warning('Durée demandée trop longue', ['dureeDemandee' => $request->duree, 'dureeDisponible' => $dureeDisponibilite]);
    //             return response()->json([
    //                 'message' => 'La durée demandée dépasse la durée disponible pour cette disponibilité.',
    //                 'details' => [
    //                     'dureeDemandee' => $request->duree,
    //                     'dureeDisponible' => $dureeDisponibilite
    //                 ]
    //             ], 400);
    //         }
    //         Log::info('Durée demandée valide', ['duree' => $request->duree]);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur de validation de la durée : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Erreur de validation de la durée.',
    //             'error' => $e->getMessage(),
    //         ], 400);
    //     }
    
    //     try {
    //         // Calcul de l'heure de fin à partir de l'heure de début et de la durée
    //         $heureDebutDemandee = strtotime($request->heureDebut);
    //         $heureFinDemandee = $heureDebutDemandee + ($request->duree * 60);
    //         Log::info('Heure de fin calculée', ['heureDebut' => $request->heureDebut, 'heureFin' => date('H:i', $heureFinDemandee)]);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur de calcul de l\'heure de fin : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Erreur de calcul de l\'heure de fin.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    
    //     try {

    //         // Calcul de l'heure de fin à partir de l'heure de début et de la durée
    //         $heureDebutDemandee = strtotime($request->heureDebut);
    //         $heureFinDemandee = $heureDebutDemandee + ($request->duree * 60);

    //         // Vérifier les conflits avec les réservations existantes
    //         $conflit = Reservation::where('prestataire_id', $disponibilite->prestataire_id)
    //                                 ->where('jour', $disponibilite->jour)
    //                                 ->where(function ($query) use ($heureDebutDemandee, $heureFinDemandee) {
    //                                     $query->whereBetween('heureDebut', [date('H:i', $heureDebutDemandee), date('H:i', $heureFinDemandee)])
    //                                         ->orWhereBetween('heureFin', [date('H:i', $heureDebutDemandee), date('H:i', $heureFinDemandee)])
    //                                         ->orWhere(function ($query) use ($heureDebutDemandee, $heureFinDemandee) {
    //                                             $query->where('heureDebut', '<=', date('H:i', $heureDebutDemandee))
    //                                                     ->where('heureFin', '>=', date('H:i', $heureFinDemandee));
    //                                         });
    //                                 })->exists();

            

    //         if ($conflit) {
    //             Log::warning('Conflit de réservation', ['heureDebutDemandee' => date('H:i', $heureDebutDemandee), 'heureFinDemandee' => date('H:i', $heureFinDemandee)]);
    //             return response()->json([
    //                 'message' => 'Le créneau horaire est déjà réservé. Veuillez choisir un autre créneau.',
    //             ], 409);
    //         }
  
    //         Log::info('Aucun conflit trouvé');
    //     } catch (\Exception $e) {
    //         Log::error('Erreur lors de la vérification des conflits : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Erreur lors de la vérification des conflits.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    
    //     try {
  
    //         // Création de la réservation
    //         $reservation = Reservation::create([
    //             'jour' => $disponibilite->jour,
    //             'heureDebut' => date('H:i', $heureDebutDemandee),
    //             'heureFin' => date('H:i', $heureFinDemandee),
    //             'prestataire_id' => $disponibilite->prestataire_id,
    //             'client_id' => $client->id,
    //         ]);
    //         Log::info('Réservation créée', ['reservation_id' => $reservation->id]);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur lors de la création de la réservation : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Erreur lors de la création de la réservation.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    
    //     try {
    //         // Création du rendez-vous
    //         $rendezVous = RendezVous::create([
    //             'duree' => $request->duree,
    //             'delaiPreReservation' => $request->delaiPreReservation,
    //             'intervalPlanification' => $request->intervalPlanification,
    //             'dureeAvantAnnulation' => $request->dureeAvantAnnulation,
    //             'disponibilite_id' => $disponibilite->id,
    //             'type_rendezvous_id' => $request->type_rendezvous_id,
    //             'client_id' => $client->id,
    //             'prestataire_id' => $disponibilite->prestataire_id,
    //             'jour' => $disponibilite->jour,
    //             'heureDebut' => $request->heureDebut,
    //             'heureFin' => date('H:i', $heureFinDemandee),
    //             'statut' => 'en attente',
    //         ]);
    //         Log::info('Rendez-vous créé', ['rendezvous_id' => $rendezVous->id]);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur lors de la création du rendez-vous : ' . $e->getMessage());
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Erreur lors de la création du rendez-vous.',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    
    //     try {
    //         // Notification au prestataire
    //         $disponibilite->prestataire->notify(new DemandeRendezVousNotification($rendezVous));
    //         Log::info('Notification envoyée au prestataire', ['prestataire_id' => $disponibilite->prestataire_id]);
    //     } catch (\Exception $e) {
    //         Log::error('Erreur lors de l\'envoi de la notification : ' . $e->getMessage());
    //     }
    
    //     try {
    //         if ($rendezVous->statut === 'valide') {
    //             $heureDebut = Carbon::parse($rendezVous->heureDebut, $rendezVous->jour);
    //             $delaiAvantNotification = $heureDebut->subMinutes(60);
    


    //         // Notification au prestataire
    //         $disponibilite->prestataire->notify(new DemandeRendezVousNotification($rendezVous));

    //         if ($rendezVous->statut === 'valide') {
    //             $heureDebut = Carbon::parse($rendezVous->heureDebut, $rendezVous->jour);
    //             $delaiAvantNotification = $heureDebut->subMinutes(60);

    //             // Planifier l'envoi de l'email
    //             NotifyClientAboutRendezVous::dispatch($rendezVous)->delay($delaiAvantNotification);
    //             Log::info('Notification client planifiée', ['rendezvous_id' => $rendezVous->id]);
    //         }
    //         return response()->json([
    //             'message' => 'Votre demande de rendez-vous a été enregistrée avec succès.',
    //             'reservation' => $reservation,
    //             'rendezVous' => $rendezVous
    //         ], 201);

    //     } catch (\Exception $e) {
    //         Log::error('Erreur lors de la planification de la notification : ' . $e->getMessage());
    //     }
    
    //     return response()->json([
    //         'message' => 'Votre demande de rendez-vous a été enregistrée avec succès.',
    //         'reservation' => $reservation,
    //         'rendezVous' => $rendezVous
    //     ], 201);
    // }
    
    //  La methode pour demander une rendez_vous 
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

        try {
            $client = JWTAuth::parseToken()->authenticate();

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

            // Vérification de la durée demandée
            $heureDebut = strtotime($disponibilite->heureDebut);
            $heureFin = strtotime($disponibilite->heureFin);
            $dureeDisponibilite = ($heureFin - $heureDebut) / 60;

            if ($request->duree > $dureeDisponibilite) {
                return response()->json([
                    'message' => 'La durée demandée dépasse la durée disponible pour cette disponibilité.',
                    'details' => [
                        'dureeDemandee' => $request->duree,
                        'dureeDisponible' => $dureeDisponibilite
                    ]
                ], 400);
            }

            // Calcul de l'heure de fin à partir de l'heure de début et de la durée
            $heureDebutDemandee = strtotime($request->heureDebut);
            $heureFinDemandee = $heureDebutDemandee + ($request->duree * 60);

            // Vérifier les conflits avec les réservations existantes
            $conflit = Reservation::where('prestataire_id', $disponibilite->prestataire_id)
                ->where('jour', $disponibilite->jour)
                ->where(function ($query) use ($heureDebutDemandee, $heureFinDemandee) {
                    $query->whereBetween('heureDebut', [date('H:i', $heureDebutDemandee), date('H:i', $heureFinDemandee)])
                        ->orWhereBetween('heureFin', [date('H:i', $heureDebutDemandee), date('H:i', $heureFinDemandee)])
                        ->orWhere(function ($query) use ($heureDebutDemandee, $heureFinDemandee) {
                            $query->where('heureDebut', '<=', date('H:i', $heureDebutDemandee))
                                ->where('heureFin', '>=', date('H:i', $heureFinDemandee));
                        });
                })->exists();

            if ($conflit) {
                return response()->json([
                    'message' => 'Le créneau horaire est déjà réservé. Veuillez choisir un autre créneau.',
                ], 409);
            }

            // Création de la réservation
            $reservation = Reservation::create([
                'jour' => $disponibilite->jour,
                'heureDebut' => date('H:i', $heureDebutDemandee),
                'heureFin' => date('H:i', $heureFinDemandee),
                'prestataire_id' => $disponibilite->prestataire_id,
                'client_id' => $client->id,
            ]);

            // Création du rendez-vous
            $rendezVous = RendezVous::create([
                'duree' => $request->duree,
                'delaiPreReservation' => $request->delaiPreReservation,
                'intervalPlanification' => $request->intervalPlanification,
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
            
            return response()->json([
                'message' => 'Votre demande de rendez-vous a été enregistrée avec succès.',
                'reservation' => $reservation,
                'rendezVous' => $rendezVous
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue s\'est produite lors de la demande de rendez-vous.',
                'error' => $e->getMessage()
            ], 500);
        }
    }





        if ($rendezVous->statut === 'valide') {
            $heureDebut = Carbon::parse($rendezVous->heureDebut, $rendezVous->jour);
            $delaiAvantNotification = $heureDebut->subMinutes(60);

            // Planifier l'envoi de l'email
            NotifyClientAboutRendezVous::dispatch($rendezVous)->delay($delaiAvantNotification);
        }

        return response()->json([
            'message' => 'Votre demande de rendez-vous a été enregistrée avec succès.',
            'reservation' => $reservation,
            'rendezVous' => $rendezVous
        ], 201);



    // La methode pour lister les rendez_vous
    public function listerRendezVous()
    {
        try {
            // Authentification de l'utilisateur à partir du token
            $client = JWTAuth::parseToken()->authenticate();

            // Récupérer les rendez-vous de l'utilisateur connecté
            $rendezVous = RendezVous::where('client_id', $client->id)
            ->with(['type_rendezvous', 'prestataire', 'disponibilite']) // Charger les relations nécessaires
            ->join('disponibilites', 'rendez_vous.disponibilite_id', '=', 'disponibilites.id') // Joindre la table disponibilités
            ->orderBy('disponibilites.jour', 'asc') // Trier par le jour depuis disponibilités
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


    // La methode pour annuler un rendez_vous
    public function annulerRendezVous(Request $request, $rendezVousId)
    {
        // Authentifier l'utilisateur via JWT sans utiliser les guards
        try {
            $client = JWTAuth::parseToken()->authenticate();
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

        // Vérifier si l'utilisateur connecté est soit le client, soit le prestataire du rendez-vous
        if ($rendezVous->client_id !== $client->id && $rendezVous->prestataire_id !== $client->id) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à annuler ce rendez-vous'], 403);
        }

        $rendezVous->statut = 'annulé';
        $rendezVous->save();


        $disponibilite = Disponibilite::where('date', $rendezVous->date)
                                    ->where('heureDebut', $rendezVous->heureDebut)
                                    ->first();

        if ($disponibilite) {
            $disponibilite->estDisponible = true;
            $disponibilite->save();
        }


        $prestataire = User::find($rendezVous->prestataire_id);

        if ($prestataire) {
            $prestataire->notify(new RendezVousAnnuleNotification($rendezVous));
        }

        return response()->json([
            'message' => 'Rendez-vous annulé avec succès',
            'rendezVous' => $rendezVous
        ]);
    }



}
