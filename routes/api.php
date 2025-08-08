<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleCalendarController;


use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrestataireController;
use App\Http\Controllers\Simple;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use App\Http\Controllers\CalendrierController;

use Illuminate\Support\Facades\Route;

Route::get('/simple', [Simple::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('/user', [AuthController::class, 'userProfile'])->middleware('auth:api');
// Route::get('/users', [NotificationController::class, 'index']);
Route::post('/auth/refresh', [AuthController::class, 'refreshToken']);


Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware(['auth:api'])->group(function () {
    Route::post('/ajouter-prestataire', [UserController::class, 'ajouterPrestataire']);
});
// api.php
Route::middleware(['auth:api'])->group(function () {
    Route::get('/admin/rendezvous', [CalendrierController::class, 'getAllRendezVous']);
});
// Dans api.php, ajoutez ces routes:

Route::middleware(['auth:api'])->group(function () {
    // Route pour les détails rendez-vous client
    Route::get('/rendezvous/{id}', [CalendrierController::class, 'showRendezVous']);
    
    // Route alternative pour admin (ou utiliser la même que prestataire)
    Route::get('/admin/rendezvous/{id}', [CalendrierController::class, 'showRendezVousAdmin']);
});
require_once __DIR__.'/prestataire.php';
require_once __DIR__.'/utilisateur.php';
