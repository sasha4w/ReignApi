<?php

// src/Middleware/CreateurMiddleware.php
namespace App\Middleware;

class CreateurMiddleware extends AbstractJWTMiddleware
{
    public function handle(): bool
    {
        $token = $this->getTokenFromHeader();
        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Token d\'authentification manquant',
                'code' => 401
            ]);
            return false;
        }

        $payload = $this->decodeToken($token);
        if (!$payload) {
            return false;
        }

        if (!$this->validateUserType($payload, 'createur')) {
            return false;
        }

        // Stockage des informations du créateur pour une utilisation ultérieure
        $_ENV['CURRENT_USER'] = [
            'id' => $payload->id_createur,
            'email' => $payload->ad_mail_createur,
            'nom' => $payload->nom_createur,
            'type' => $payload->userType
        ];

        return true;
    }
}