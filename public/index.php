<?php

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Indiquer que toutes les origines sont autorisées
    header("Access-Control-Allow-Origin: *");
    // Autoriser les méthodes HTTP comme GET, POST, PUT, PATCH, DELETE
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE");
    // Autoriser les en-têtes Content-Type et Authorization (si tu utilises des tokens)
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    // Répondre avec un code 200 OK
    http_response_code(200);
    exit(); // Terminer ici pour éviter d'exécuter la logique du code derrière
}

// En-têtes CORS pour toutes les autres requêtes
header("Access-Control-Allow-Origin: *"); // Autoriser toutes les origines
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE"); // Autoriser ces méthodes
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Autoriser ces en-têtes

require '../src/bootstrap.php'; ?>
