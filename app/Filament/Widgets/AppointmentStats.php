<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppointmentStats extends BaseWidget
{
    protected static ?string $pollingInterval = '3s';
    protected static bool $isLazy = \false;
    protected ?string $heading = 'Analyse';
    protected ?string $description = 'Un aperçu de quelques analyses.';
 
    protected function getStats(): array
    {
        $totalAppointments = \App\Models\RendezVous::count();
        $validAppointments = \App\Models\RendezVous::where('statut', 'valide')->count();
        $cancelledAppointments = \App\Models\RendezVous::where('statut', 'annule')->count();
        $pendingAppointments = \App\Models\RendezVous::where('statut', 'en attente')->count();
        
        $validRate = $totalAppointments > 0 ? round(($validAppointments / $totalAppointments) * 100, 2) : 0;
        $cancelledRate = $totalAppointments > 0 ? round(($cancelledAppointments / $totalAppointments) * 100, 2) : 0;
        $pendingRate = $totalAppointments > 0 ? round(($pendingAppointments / $totalAppointments) * 100, 2) : 0;
        
        return [
            Stat::make('Nombre de Rendez-vous', $totalAppointments)
                ->description('Total des rendez-vous')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('primary'),
    
            Stat::make('Taux de Rendez-vous Valides', "{$validRate}%")
                ->description('Pourcentage de rendez-vous validés')
                ->descriptionIcon('heroicon-m-check')
                ->color('success'),
    
            Stat::make('Taux de Rendez-vous Annulés', "{$cancelledRate}%")
                ->description('Pourcentage de rendez-vous annulés')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
    
            Stat::make('Taux de Rendez-vous en Attente', "{$pendingRate}%")
                ->description('Pourcentage de rendez-vous en attente')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
    
    }
