<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Mail\SendUserPassword;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\Permission\Models\Role;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $label = 'Utilisateurs';
    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Gestion administrative';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')

                    ->label('Nom')

                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('telephone')
                    ->label('Téléphone')
                    ->numeric()
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
                    ->default(true)
                    ->afterStateUpdated(function ($state, $record) {
                        // S'assurer que le record existe déjà (lors de la modification et non de la création)
                        if ($record->exists) {
                            $record->update(['is_active' => $state]);
                        }
                    })
                // On retire le champ role_id du formulaire
                // La logique du rôle par défaut sera gérée automatiquement dans le modèle
            ]);
    }


    public static function beforeSave($record): void
    {
        // Générer un mot de passe aléatoire
        $password = Str::random(12);

        // Attribuer le mot de passe haché au modèle utilisateur
        $record->password = Hash::make($password);

        // Si l'enregistrement est créé par l'admin via Filament et qu'un rôle est sélectionné
        if (!empty($record->role_id)) {
            $role = Role::find($record->role_id);
            if ($role) {
                $record->role = $role->name; // Remplace la valeur par défaut par le rôle sélectionné
            }
        }

        // Envoyer le mot de passe à l'utilisateur par email
        Mail::to($record->email)->send(new \App\Mail\SendUserPassword($record->name, $password, $record->email));
    }

    // Ajoutez cette méthode pour assigner le rôle après la sauvegarde
    // public static function afterSave($record): void
    // {
    //     // Assigner le rôle Spatie en fonction de la valeur de la colonne role
    //     if (!empty($record->role)) {
    //         $record->syncRoles([$record->role]);
    //     } else {
    //         // Si pour une raison quelconque le rôle est vide, utiliser "utilisateur"
    //         $record->role = 'utilisateur';
    //         $record->save();
    //         $record->syncRoles(['utilisateur']);
    //     }
    // }
    public static function afterSave($record): void
    {
        if ($record->role_id) {
            $role = Role::find($record->role_id);
            $record->syncRoles([$role->name]); // Met à jour Spatie
            $record->role = $role->name; // Met à jour le champ "role" dans la table users
            $record->saveQuietly(); // Évite les boucles de sauvegarde
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom de l\'utilisateur')
                    ->sortable()
                    ->searchable(),
                // Tables\Columns\BooleanColumn::make('is_admin')
                //     ->boolean()
                //     ->sortable()
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('role')
                //     ->sortable()
                //     ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                // Tables\Columns\TextColumn::make('deleted_at')
                //     ->dateTime('d-M-Y')
                //     ->sortable()
                //     ->searchable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Rôle')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label("enregistré le")
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Statut')
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                    ])
                    ->default('active') // Valeur par défaut
                    ->sortable()
                    ->searchable()
                    ->toggleable() // Permet de masquer/afficher la colonne
                    ->afterStateUpdated(function ($state, $record) {
                        // Mettre à jour le statut dans la base de données
                        $record->update(['status' => $state]);
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('Non-Admins')
                    ->query(fn(Builder $query) => $query->where('is_admin', '!=', 1))
                    ->default(), // Ce filtre est appliqué par défaut
            ]);
        // ->actions([
        //     Tables\Actions\EditAction::make(),
        //     Tables\Actions\DeleteAction::make(),
        // ])
        // ->bulkActions([
        //     Tables\Actions\DeleteBulkAction::make(),
        // ]);
    }
    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

    // Tables\Columns\TextColumn::make('email_verified_at')
    //     ->dateTime(),
    //     ->sortable(),
    // Tables\Columns\TextColumn::make('auth à deux facteurs'),
    // Tables\Columns\TextColumn::make('code de récupération'),
    // Tables\Columns\TextColumn::make('deux facteurs confirmés à')
    //     ->dateTime(),
    // Tables\Columns\TextColumn::make('Id équipe actuelle'),
    // Tables\Columns\TextColumn::make('chemin photo de profil'),
    // Tables\Columns\TextColumn::make('créé à')
    //     ->dateTime(),
    //     ->sortable()
    //     ->toggleable(isToggledHiddenByDefault: true),
    // Tables\Columns\TextColumn::make('mis à jour à')
    //     ->dateTime(),
    //     ->sortable()
    //     ->toggleable(isToggledHiddenByDefault: true),








    // Forms\Components\Textarea::make('auth à deux facteurs')
    //     ->maxLength(65535),
    // Forms\Components\Textarea::make('code de récupération')
    //     ->maxLength(65535),
    // Forms\Components\DateTimePicker::make('deux facteurs confirmés à'),
    // Forms\Components\TextInput::make('Id équipe actuelle'),
    // Forms\Components\TextInput::make('chemin photo de profil')
    //     ->maxLength(2048),
