<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrestataireController;


use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;




Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::get('/user', [AuthController::class, 'userProfile'])->middleware('auth:api');
Route::get('/users', [NotificationController::class, 'index']);





require_once __DIR__.'/prestataire.php';
require_once __DIR__.'/utilisateur.php';



