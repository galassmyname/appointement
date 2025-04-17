<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Disponibilite;

use App\Models\RendezVous;
use App\Models\TypeRendezVous;
use App\Models\User; // Client
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;



class AppointmentResource extends Resource
{
    protected static ?string $model = RendezVous::class;

    protected static ?string $pluralLabel = 'Rendez-vous';
    protected static ?string $label = 'Rendez-vous';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Gestion administrative';

    // Formulaire de création/édition
    public static function form(Forms\Form $form): Forms\Form
    {
            return $form
            ->schema([
                Select::make('disponibilite_id')
                    ->label('Disponibilité')
                    ->options(
                        Disponibilite::with('prestataire')
                            ->get()
                            ->mapWithKeys(function ($disponibilite) {
                                return [
                                    $disponibilite->id => $disponibilite->jour . ' - ' . $disponibilite->prestataire->name,
                                ];
                            })
                    )
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $disponibilite = Disponibilite::find($state);
                        if ($disponibilite && $disponibilite->prestataire) {
                            $set('prestataire_affichage', $disponibilite->prestataire->name . ' - ' . $disponibilite->prestataire->specialite);
                            $set('prestataire_id', $disponibilite->prestataire->id);
                        } else {
                            $set('prestataire_affichage', 'Aucun prestataire associé');
                            $set('prestataire_id', null);
                        }
                    }),
        
                Select::make('type_rendezvous_id')
                    ->label('Type de Rendez-vous')
                    ->options(TypeRendezVous::pluck('nomService', 'id'))
                    ->required()
                    ->searchable(),
        
                Select::make('client_id')
                    ->label('Client')
                    ->options(
                        User::where('role', 'utilisateur')
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->searchable(),
        
                Hidden::make('prestataire_id')
                    ->required(),
        
                TextInput::make('prestataire_affichage')
                    ->label('Prestataire')
                    ->readOnly() 
                    ->required()
                    ->reactive(),

        
                    TextInput::make('duree')
                    ->label('Durée (en minutes)')
                    ->required()
                    ->numeric()
                    ->minValue(15)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $heureDebut = $get('heureDebut');
                        if ($heureDebut && is_numeric($state)) {
                            try {
                                $heureFin = Carbon::parse($heureDebut)
                                    ->addMinutes((int)$state)
                                    ->format('H:i');
                                $set('heureFin', $heureFin);
                            } catch (\Exception $e) {
                                Log::error('Erreur de calcul de heureFin', ['message' => $e->getMessage()]);
                            }
                        }
                    }),
        
                TextInput::make('delaiPreReservation')
                    ->label('Délai pres-réservation (en minutes)')
                    ->required()
                    ->numeric(),
        
                TextInput::make('intervalPlanification')
                    ->label('Intervalle de planification (en jours)')
                    ->required()
                    ->numeric(),
        
                TextInput::make('dureeAvantAnnulation')
                    ->label('Durée avant annulation (en minutes)')
                    ->required()
                    ->numeric(),
        
                    Select::make('heureDebut')
                    ->label('Heure de début')
                    ->options(function ($get) {
                        $disponibiliteId = $get('disponibilite_id');
                        $duree = $get('duree');
                        $prestataireId = $get('prestataire_id'); // Récupération de l'ID du prestataire
                
                        if (!$disponibiliteId || !$duree || !$prestataireId) {
                            Log::info('Valeurs manquantes pour heureDebut', [
                                'disponibilite_id' => $disponibiliteId,
                                'duree' => $duree,
                                'prestataire_id' => $prestataireId,
                            ]);
                            return [];
                        }
                
                        $disponibilite = Disponibilite::find($disponibiliteId);
                        if (!$disponibilite) {
                            Log::info('Disponibilité introuvable', ['disponibilite_id' => $disponibiliteId]);
                            return [];
                        }
                
                        // Récupérer toutes les plages horaires disponibles
                        $plagesHoraires = $disponibilite->calculerPlagesHoraires((int)$duree);
                
                        // Filtrer les plages qui ne sont pas en conflit avec des rendez-vous existants
                        $horairesDisponibles = [];
                        foreach ($plagesHoraires as $horaire) {
                            $heureDebut = Carbon::parse($horaire);
                            $heureFin = $heureDebut->copy()->addMinutes((int)$duree);
                
                            $conflict = RendezVous::hasConflict($prestataireId, $heureDebut, $heureFin);
                            if (!$conflict) {
                                $horairesDisponibles[$horaire] = $horaire; // Ajouter uniquement les plages sans conflit
                            }
                        }
                
                        return $horairesDisponibles;
                    })
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $duree = $get('duree');
                        if ($duree && is_numeric($duree)) {
                            try {
                                $heureFin = Carbon::parse($state)
                                    ->addMinutes((int)$duree)
                                    ->format('H:i');
                                $set('heureFin', $heureFin);
                            } catch (\Exception $e) {
                                Log::error('Erreur de calcul de heureFin', ['message' => $e->getMessage()]);
                            }
                        }
                    }),
                
                
                TextInput::make('heureFin')
                ->label('Heure de fin')
                ->readOnly() 
                ->required()
                ->reactive(),
                
        
                // Select::make('statut')
                //     ->label('Statut')
                //     ->options([
                //         'en attente' => 'En attente',
                //         'validé' => 'validé',
                //     ])
                //     ->required(),


                TextInput::make('conflit')
                ->label('Conflit')
                ->readOnly()
                ->hidden(fn ($get) => !$get('conflit')) // Cache le champ s’il n’y a pas de conflit
                ->suffix('⚠️ Conflit détecté ! Veuillez modifier l’horaire.')
                ->reactive(),
                
            ]);
        }
    

    
    // Table des rendez-vous

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable(),
                
                TextColumn::make('prestataire.name')
                    ->label('Prestataire')
                    ->sortable(),
                
                TextColumn::make('type_rendezvous.nomService')
                    ->label('Type de Rendez-vous')
                    ->sortable(),
    
                TextColumn::make('heureDebut')
                    ->label('Heure de début')
                    ->dateTime(),
                
                TextColumn::make('heureFin')
                    ->label('Heure de fin')
                    ->dateTime(),
    
                TextColumn::make('statut')
                    ->label('Statut')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Action "Annuler" pour le statut "validé"
                    Tables\Actions\Action::make('annuler')
                        ->label('Annuler')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn ($record) => $record->statut === 'validé')
                        ->action(function ($record) {
                            $record->statut = 'annulé';
                            $record->save();
                            Log::info('Rendez-vous annulé : ' . $record->id);
                        })
                        ->requiresConfirmation(),
    
                    // Action "Valider" pour les statuts "annulé" ou "en attente"
                    Tables\Actions\Action::make('valider')
                        ->label('Valider')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn ($record) => $record->statut === 'annulé' || $record->statut === 'en attente')
                        ->action(function ($record) {
                            $record->statut = 'validé';
                            $record->save();
                            Log::info('Rendez-vous validé : ' . $record->id);
                        })
                        ->requiresConfirmation(),
    
                    // Action "Annuler" pour le statut "en attente"
                    Tables\Actions\Action::make('annuler_en_attente')
                        ->label('Annuler')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->visible(fn ($record) => $record->statut === 'en attente')
                        ->action(function ($record) {
                            $record->statut = 'annulé';
                            $record->save();
                            Log::info('Rendez-vous annulé : ' . $record->id);
                        })
                        ->requiresConfirmation(),
    
                    // Action de modification
                    Tables\Actions\EditAction::make()
                        ->label('Modifier'),
    
                    // Action de suppression
                    Tables\Actions\DeleteAction::make()
                        ->label('Supprimer')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation(),
                ])//->icon('heroicon-o-dots-vertical') // Icône pour le menu de trois points
                ->tooltip('Actions') // Info-bulle pour le menu
            ]);
    }
        

    public static function getRelations(): array
    {
        return [];
    }

    // Récupérer les pages de la ressource
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }
}
