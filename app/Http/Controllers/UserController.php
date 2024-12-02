<?php

namespace App\Http\Controllers;


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
use Illuminate\Support\Facades\Notification;
use Tymon\JWTAuth\Facades\JWTAuth;
//use prestataire;

class UserController extends Controller
{



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
        // Validation des données d'entrée
        $request->validate([
            'jour' => 'required|string|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
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

        foreach ($disponibilites as $disponibilite) {
            $heureDebut = strtotime($disponibilite->heureDebut); // Convertir l'heure de début en timestamp
            $heureFin = strtotime($disponibilite->heureFin); // Convertir l'heure de fin en timestamp
            $dureeDisponibilite = ($heureFin - $heureDebut) / 60; // Durée de la disponibilité en minutes

            // Vérifier si la durée de la réunion dépasse la durée de la disponibilité
            if ($dureeReunion > $dureeDisponibilite) {
                return response()->json([
                    'message' => 'La durée choisie dépasse la durée de la disponibilité pour ce prestataire.',
                    'details' => [
                        'heureDebut' => $disponibilite->heureDebut,
                        'heureFin' => $disponibilite->heureFin,
                        'dureeDisponibilite' => $dureeDisponibilite
                    ]
                ], 400);
            }

            // Créer des plages horaires selon la durée de la réunion
            while (($heureDebut + ($dureeReunion * 60)) <= $heureFin) {
                $nouvellePlage = [
                    'heureDebut' => $heureDebut,
                    'heureFin' => $heureDebut + ($dureeReunion * 60),
                ];

                // Vérifier les conflits avec les réservations existantes
                $conflit = $reservations->first(function ($reservation) use ($nouvellePlage) {
                    return $nouvellePlage['heureDebut'] < $reservation['heureFin'] &&
                        $nouvellePlage['heureFin'] > $reservation['heureDebut'];
                });

                if (!$conflit) {
                    $plagesHoraires[] = [
                        'heureDebut' => date('H:i', $nouvellePlage['heureDebut']),
                        'heureFin' => date('H:i', $nouvellePlage['heureFin']),
                    ];
                }

                $heureDebut += ($dureeReunion * 60); // Avancer à la plage horaire suivante
            }
        }

        // Vérifier si des plages horaires ont été calculées
        if (empty($plagesHoraires)) {
            return response()->json(['message' => 'Aucune plage horaire disponible pour la durée choisie ou en raison de conflits avec des réservations existantes.'], 404);
        }

        // Retourner les plages horaires calculées
        return response()->json(['data' => $plagesHoraires], 200);
    } 
        


    
    //La methode pour demander un rendez_vous
    public function creerRendezVous(Request $request, $idPrestataire)
    {
        // Validation des données d'entrée
        $request->validate([
            'jour' => 'required|string|in:Monday,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'heureDebut' => 'required|date_format:H:i',
            'duree' => 'required|integer|min:15', // Durée en minutes
            'delaiPreReservation' => 'required|integer|min:0', // Délai en minutes avant la réunion
            'intervalPlanification' => 'required|integer|min:1', // Durée de validité de la planification en jours
            'dureeAvantAnnulation' => 'required|integer|min:0', // Temps avant lequel l'annulation est autorisée
            'disponibilite_id' => 'required|exists:disponibilites,id',
            'type_rendezvous_id' => 'required|exists:type_rendez_vous,id'
        ]);
    
        $now = now(); 
        $disponibilite = Disponibilite::with('prestataire')->findOrFail($request->disponibilite_id);
    
        // Vérification : le prestataire doit exister
        $prestataire = $disponibilite->prestataire;
        if (!$prestataire) {
            return response()->json(['message' => 'Prestataire introuvable pour cette disponibilité'], 404);
        }
    
        // Calcul de la date et heure de début à partir de la disponibilité
        $startDateTime = $now->copy()->next(\Carbon\Carbon::parse($request->jour)->format('l')) 
            ->setTimeFromTimeString($request->heureDebut);
    
        // Vérification du délai de pré-réservation
        if ($now->diffInMinutes($startDateTime) < $request->delaiPreReservation) {
            return response()->json(['message' => 'Le délai de pré-réservation n\'est pas respecté'], 400);
        }
    
        // Création du rendez-vous
        $rendezVous = RendezVous::create([
            'disponibilite_id' => $request->disponibilite_id,
            'duree' => $request->duree,
            'delaiPreReservation' => $request->delaiPreReservation,
            'intervalPlanification' => $request->intervalPlanification,
            'dureeAvantAnnulation' => $request->dureeAvantAnnulation,
            'client_id' => auth('api')->user()->id,
            'prestataire_id' => $idPrestataire,
            'type_rendezvous_id' => $request->type_rendezvous_id,
            'statut' => 'en attente', // Statut par défaut
        ]);
    
        // Envoyer une notification par mail au prestataire
       // $prestataire->notify(new DemandeRendezVousNotification($rendezVous));
    
        return response()->json([
            'message' => 'Rendez-vous créé avec succès', 
            'rendezVous' => $rendezVous
        ], 201);
    }
    
    
}