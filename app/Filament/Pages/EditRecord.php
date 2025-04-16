<?php
namespace App\Filament\Pages;

use Filament\Resources\Pages\EditRecord as FilamentEditRecord;

abstract class EditRecord extends FilamentEditRecord
{
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}