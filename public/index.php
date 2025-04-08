<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Déterminez si l'application est en mode maintenance...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Enregistrez l'autoloader de Composer...
require __DIR__.'/../vendor/autoload.php';

// Démarrez Laravel et gérez la requête...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
