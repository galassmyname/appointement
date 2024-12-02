<?php

use App\Http\Controllers\DisponibiliteController;
use App\Http\Controllers\PrestataireController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


Route::prefix('prestataire')->group(function () {
    Route::post('/register', [PrestataireController::class, 'prestataireRegister']);
    Route::post('/login', [PrestataireController::class, 'login']);
    Route::post('/logout', [PrestataireController::class, 'logout'])->middleware('auth:prestataire');
});



Route::middleware('auth:api')->group(function () {
    Route::prefix('prestataire')->group(function () {
        Route::post('/disponibilites', [PrestataireController::class, 'definirDisponibilites']);
        Route::get('/listedisponibilites', [PrestataireController::class, 'listerDisponibilites']);
        Route::put('/disponibilites/{id}', [PrestataireController::class, 'modifierDisponibilite']);
        Route::delete('/disponibilites/{id}', [PrestataireController::class, 'supprimerDisponibilite']);

        
       // Route::post('/disponibilites', [PrestataireController::class, 'store']);


        Route::get('/rendezvous', [PrestataireController::class, 'listerRendezVousPrestataire']);
        Route::post('/rendezvous/{rendezVousId}/valider', [PrestataireController::class, 'validerRendezVous']);
        Route::post('/rendezvous/{rendezVousId}/annuler', [PrestataireController::class, 'annulerRendezVousParPrestataire']);


    });
});

