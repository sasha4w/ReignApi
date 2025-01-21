<?php
// src/Middleware/AdminMiddleware.php
namespace App\Middleware;

class AdminMiddleware extends AbstractJWTMiddleware
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

        if (!$this->validateUserType($payload, 'administrateur')) {
            return false;
        }

        // Stockage des informations de l'admin pour une utilisation ultérieure
        $_ENV['CURRENT_USER'] = [
            'id' => $payload->id_administrateur ?? null,
            'email' => $payload->ad_mail_administrateur ?? null,
            'nom' => $payload->nom_administrateur ?? null,
            'type' => $payload->userType
        ];

        return true;
    }
}

