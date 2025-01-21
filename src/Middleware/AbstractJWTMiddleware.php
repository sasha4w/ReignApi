<?php
// src/Middleware/AbstractJWTMiddleware.php
namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Dotenv\Dotenv;

abstract class AbstractJWTMiddleware
{
    protected string $jwtSecret;

    public function __construct()
    {
        // Charger les variables d'environnement si ce n'est pas déjà fait
        if (!isset($_ENV['JWT_SECRET'])) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }
        
        $this->jwtSecret = $_ENV['JWT_SECRET'];
    }

    protected function getTokenFromHeader(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($header)) {
            return null;
        }
        
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    protected function decodeToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        } catch (ExpiredException $e) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Token expiré',
                'code' => 401
            ]);
            return null;
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Token invalide',
                'code' => 401
            ]);
            return null;
        }
    }

    protected function validateUserType(object $payload, string $expectedType): bool
    {
        if ($payload->userType !== $expectedType) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Accès non autorisé pour ce type d\'utilisateur',
                'code' => 403
            ]);
            return false;
        }
        return true;
    }
}

