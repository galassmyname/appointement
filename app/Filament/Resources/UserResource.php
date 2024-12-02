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


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'gestion administrative';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                    CheckboxList::make('roles')
                    ->relationship('roles', 'id') // Utilisez 'id' pour récupérer les rôles par leur identifiant
                    ->columns(2)
                    ->helperText('Sélectionnez les rôles.')
                    ->required(),
                
            ]);
    }

    public static function beforeSave($record): void
    {
        // Générer un mot de passe aléatoire
        $password = Str::random(12);
    
        // Attribuer le mot de passe haché au modèle utilisateur
        $record->password = Hash::make($password);
    
        // Envoyer le mot de passe à l'utilisateur par email
        Mail::to($record->email)->send(new \App\Mail\SendUserPassword($record->name, $password, $record->email));

    }
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\BooleanColumn::make('is_admin')
                    ->boolean()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('deleted_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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