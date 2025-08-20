<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Rendezvous;
use App\Models\Prestataire;
use App\Models\User;

class DashboardStats extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-stats';

    public $totalRendezvous;
    public $totalPrestataires;
    public $totalClients;

    public function mount(): void
    {
        $this->totalRendezvous = Rendezvous::count();
        $this->totalPrestataires = Prestataire::count();
        $this->totalClients = User::where('role', 'client')->count();
    }
}
