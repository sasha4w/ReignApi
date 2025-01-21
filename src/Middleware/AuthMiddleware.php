<?php

// src/Middleware/AuthMiddleware.php
namespace App\Middleware;

class AuthMiddleware extends AbstractJWTMiddleware
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

        // Pour Auth, on stocke les infos quel que soit le type d'utilisateur
        $_ENV['CURRENT_USER'] = [
            'type' => $payload->userType,
            'id' => $payload->id_createur ?? $payload->id_administrateur ?? null,
            'email' => $payload->ad_mail_createur ?? $payload->ad_mail_administrateur ?? null,
            'nom' => $payload->nom_createur ?? $payload->nom_administrateur ?? null
        ];

        return true;
    }
}