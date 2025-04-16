<?php

namespace App\Filament\Resources\TypeRendezVousResource\Pages;

use App\Filament\Resources\TypeRendezVousResource;
use Filament\Actions;
use App\Filament\Pages\EditRecord;
// use Filament\Resources\Pages\EditRecord;

class EditTypeRendezVous extends EditRecord
{
    protected static string $resource = TypeRendezVousResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
