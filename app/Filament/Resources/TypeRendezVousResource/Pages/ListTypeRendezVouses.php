<?php

namespace App\Filament\Resources\TypeRendezVousResource\Pages;

use App\Filament\Resources\TypeRendezVousResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeRendezVouses extends ListRecords
{
    protected static string $resource = TypeRendezVousResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
