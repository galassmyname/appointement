<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrestataireController;
use App\Http\Controllers\UserController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;




Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/listerCreneauxDispo', [UserController::class, 'listerCreneauDispo']);

    Route::post('/demandeRendezVous', [UserController::class, 'demanderRendezVous']);
    Route::get('/rendezvous', [UserController::class, 'listerRendezVous']);
    //les types de rendez vous
    Route::get('/listerTypeDeRV', [UserController::class, 'listerTypeDeRV']);
    Route::post('/rendezvous/{rendezVousId}/annuler', [UserController::class, 'annulerRendezVous']);




    // les routes pour le processus de demande de rendez-vous
    Route::get('disponibilitesChoisie/{id}', [UserController::class, 'DisponibilitesPrestataireChoisi']);// pour lister les disponibilites d'un prestataires
    Route::get('/listeDisponibilites', [UserController::class, 'listerDisponibilitesPrestataires']);// pour lister les disponi bilites de tout les prestataires
    Route::post('/dureeRendezvous/{prestataireId}', [UserController::class, 'obtenirPlagesDisponibles']);// pres avoire choisi une disponibilites pour determiner sa duree de rendez_vous
    Route::post('/demandeRendezVous/{prestataireId}', [UserController::class, 'demanderRendezVous']);// demander un rendez_vous


    Route::get('/listerRendezVous', [UserController::class, 'listerRendezVous']);// demander un rendez_vous
});





Route::get('/test-email', function () {
    $details = [
        'subject' => 'Test d\'envoi d\'email',
        'body' => 'Ceci est un e-mail de test pour vérifier la configuration.'
    ];

    Mail::raw($details['body'], function ($message) use ($details) {
        $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        $message->to('destinataire@example.com');
        $message->subject($details['subject']);
    });

    return 'E-mail de test envoyé avec succès !';
});
