<?php

use App\Http\Controllers\DisponibiliteController;
use App\Http\Controllers\PrestataireController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;


Route::prefix('prestataire')->group(function () {
    Route::post('/register', [PrestataireController::class, 'prestataireRegister']);
    Route::post('/login', [PrestataireController::class, 'prestataireLogin']);
    Route::post('/logout', [PrestataireController::class, 'logout'])->middleware('auth:api');
    Route::post('/auth/refresh', [PrestataireController::class, 'refreshToken'])->middleware('auth:api');;
});



Route::middleware('auth:prestataire')->group(function () {
    Route::prefix('prestataire')->group(function () {
        Route::post('/disponibilites', [PrestataireController::class, 'definirDisponibilites']);
        Route::get('/listedisponibilites', [PrestataireController::class, 'listerDisponibilites']);
        Route::put('/disponibilites/{id}', [PrestataireController::class, 'modifierDisponibilite']);
        Route::delete('/disponibilites/{id}', [PrestataireController::class, 'supprimerDisponibilite']);


        Route::get('/rendezvous', [PrestataireController::class, 'listerRendezVousPrestataire']);
        Route::post('/rendezvous/{rendezVousId}/valider', [PrestataireController::class, 'validerRendezVous']);
        Route::post('/rendezvous/{rendezVousId}/annuler', [PrestataireController::class, 'annulerRendezVous']);


    });
});

