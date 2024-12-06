<?php

namespace App\Http\Controllers;

use App\Models\Prestataire;
use App\Models\Rendezvous;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Nombre total de rendez-vous
        $totalRendezvous = Rendezvous::count();

        // Nombre total de prestataires
        $totalPrestataires = Prestataire::count();

        // Nombre total de clients
        $totalClients = User::where('role', 'client')->count();

        // Taux de rendez-vous validés
        $validatedRendezvous = Rendezvous::where('status', 'validé')->count();
        $validationRate = $totalRendezvous ? round(($validatedRendezvous / $totalRendezvous) * 100, 2) : 0;

        // Taux de rendez-vous annulés
        $cancelledRendezvous = Rendezvous::where('status', 'annulé')->count();
        $cancellationRate = $totalRendezvous ? round(($cancelledRendezvous / $totalRendezvous) * 100, 2) : 0;

        // Retourner les données vers la vue
        return view('dashboard', compact(
            'totalRendezvous',
            'totalPrestataires',
            'totalClients',
            'validationRate',
            'cancellationRate'
        ));
    }
}
