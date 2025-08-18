<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RendezVous; // Add this import

class CalendrierController extends Controller
{
    
    public function getAllRendezVous()
    {
        try {
            $rendezvous = RendezVous::with(['type_rendezvous', 'client', 'prestataire', 'disponibilite'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Tous les rendez-vous récupérés avec succès',
                'rendezVous' => $rendezvous
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function showRendezVous($id)
{
    try {
        $user = auth()->user();
        
        $rendezVous = RendezVous::where('client_id', $user->id)
            ->where('id', $id)
            ->with(['type_rendezvous', 'prestataire', 'disponibilite'])
            ->first();

        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        return response()->json([
            'status' => 'success',
            'rendezvous' => $rendezVous
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue',
            'error' => $e->getMessage()
        ], 500);
    }
    
}
public function showRendezVousAdmin($id)
{
    try {
        $rendezVous = RendezVous::with(['type_rendezvous', 'client', 'prestataire', 'disponibilite'])
            ->find($id);

        if (!$rendezVous) {
            return response()->json(['message' => 'Rendez-vous non trouvé'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $rendezVous
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Une erreur est survenue',
            'error' => $e->getMessage()
        ], 500);
    }
}

}