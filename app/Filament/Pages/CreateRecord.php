<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\CreateRecord as FilamentCreateRecord;

abstract class CreateRecord extends FilamentCreateRecord
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}