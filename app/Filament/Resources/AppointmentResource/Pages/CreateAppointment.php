<?php

namespace App\Filament\Resources\AppointmentResource\Pages;

use App\Filament\Resources\AppointmentResource;
use Filament\Actions;
use App\Filament\Pages\CreateRecord;
// use Filament\Resources\Pages\CreateRecord;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

     // Ajouter cette méthode pour rediriger vers la liste
    //  protected function getRedirectUrl(): string
    //  {
    //     return $this->getResource()::getUrl('index');
    //  }
}
