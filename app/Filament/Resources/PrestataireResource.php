<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrestataireResource\Pages\CreatePrestataire;
use App\Filament\Resources\PrestataireResource\Pages\EditPrestataire;
use App\Filament\Resources\PrestataireResource\Pages\ListPrestataires;
use App\Models\Prestataire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\Permission\Models\Role;


class PrestataireResource extends Resource
{
    protected static ?string $model = Prestataire::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'gestion administrative';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom du Prestataire')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('telephone')
                    ->label('Téléphone')
                    ->tel()
                    ->required(),
                Forms\Components\TextInput::make('specialite')
                    ->label('Spécialité')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role_id')
                    ->label('Rôle')
                    ->options(Role::all()->pluck('name', 'id'))
                    ->required()
                    ->searchable(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }
    
    public static function beforeSave($record): void
    {
        // Générer un mot de passe aléatoire
        $password = Str::random(12);
    
        // Attribuer le mot de passe haché au modèle utilisateur
        $record->password = Hash::make($password);
    
        // Envoyer le mot de passe à l'utilisateur par email
        Mail::to($record->email)->send(new \App\Mail\PrestatairePasswordMail($record->name, $password, $record->email));

    }
    


    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.name') // Utilise la relation `role` pour accéder au champ `name`
                    ->label('Role') // Intitulé de la colonne
                    ->sortable() // Permet le tri
                    ->searchable(), // Permet la recherche
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->sortable(),
                Tables\Columns\TextColumn::make('specialite')
                    ->label('Specialite')
                    ->sortable(),
                Tables\Columns\BooleanColumn::make('is_active')
                    ->label('Actif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d-M-Y')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn ($record) => $record->is_active ? 'Désactiver' : 'Activer') // Libellé dynamique
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success') // Couleur du bouton
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle') // Icône dynamique
                    ->action(function ($record) {
                        $record->is_active = !$record->is_active; // Inverser l'état actif
                        $record->save();
                    })
                    ->requiresConfirmation() // Confirmation avant l'exécution
                    ->tooltip(fn ($record) => $record->is_active ? 'Désactiver ce prestataire' : 'Activer ce prestataire'),
                // Action de suppression définitive
                Tables\Actions\DeleteAction::make()
                    ->label('Supprimer') // Libellé du bouton
                    ->color('danger') // Couleur rouge
                    ->icon('heroicon-o-trash') // Icône de la corbeille
                    ->action(function ($record) {
                        $record->forceDelete(); // Supprimer définitivement le prestataire
                    })
                    ->requiresConfirmation() // Demande une confirmation avant suppression
                    ->tooltip('Supprimer ce prestataire définitivement'), // Tooltip pour plus de clarté
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrestataires::route('/'),
            'create' => CreatePrestataire::route('/create'),
            'edit' => EditPrestataire::route('/{record}/edit'),
        ];
    }
}
