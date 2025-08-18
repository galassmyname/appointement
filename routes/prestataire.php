<?php

use App\Http\Controllers\DisponibiliteController;
use App\Http\Controllers\PrestataireController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')->group(function () {
    Route::prefix('prestataire')->group(function () {
        Route::post('/disponibilites', [PrestataireController::class, 'definirDisponibilites']);
        Route::get('/disponibilitesParJ/{jour}', [PrestataireController::class, 'getDisponibilite']);
        Route::get('/disponibilites/{id}', [PrestataireController::class, 'getDisponibiliteById']);
        Route::get('/listedisponibilites', [PrestataireController::class, 'listerDisponibilites']);
        Route::put('/disponibilites/{id}', [PrestataireController::class, 'modifierDisponibilite']);
        Route::delete('/disponibilites/{id}', [PrestataireController::class, 'supprimerDisponibilite']);


        Route::get('/rendezvous', [PrestataireController::class, 'listerRendezVousPrestataire']);
        Route::post('/rendezvous/{rendezVousId}/valider', [PrestataireController::class, 'validerRendezVous']);
        Route::post('/rendezvous/{rendezVousId}/annuler', [PrestataireController::class, 'annulerRendezVous']);
        Route::post('/rendezvous/{rendezVousId}/annulation-urgence', [PrestataireController::class, 'annulationUrgence']);
        Route::get('/rendezvous/annulations-urgence/verifier', [PrestataireController::class, 'verifierAnnulationsRestantes']);

      
        Route::get('/rendezvous/{rendezVousId}', [PrestataireController::class, 'showRendezVous']);
    });
});