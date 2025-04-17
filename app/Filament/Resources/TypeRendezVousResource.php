<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TypeRendezVousResource\Pages;
use App\Models\TypeRendezVous;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TypeRendezVousResource extends Resource
{
    protected static ?string $model = TypeRendezVous::class;

    protected static ?string $label = 'Type de Rendez-vou';
    protected static ?string $navigationIcon = 'heroicon-s-tag';
    protected static ?string $navigationGroup = 'Gestion administrative';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nomService')
                    ->required()
                    ->maxLength(255)
                    ->label('Nom du Service'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(5),
                Select::make('priorite')
                    ->label('Priorité')
                    ->options([
                        '1' => 'Haute',
                        '2' => 'Moyenne',
                        '3' => 'Basse',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomService')
                    ->label('Nom du Service')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),
                TextColumn::make('priorite')
                    ->label('Priorité')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTypeRendezVous::route('/'),
            'create' => Pages\CreateTypeRendezVous::route('/create'),
            'edit' => Pages\EditTypeRendezVous::route('/{record}/edit'),
        ];
    }
}
