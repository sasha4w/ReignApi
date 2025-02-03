<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Administrateur;
use App\Model\Createur;
use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;
use Exception; // Ajout de cet import

class AuthController extends Controller 
{
    protected function unifiedLogin(string $userType)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Méthode non autorisée.']);
            http_response_code(405);
            return;
        }

        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');  
        $dotenv->load();
        $data = json_decode(file_get_contents('php://input'), true);

        // Configuration selon le type d'utilisateur
        $config = [
            'administrateur' => [
                'model' => Administrateur::class,
                'emailField' => 'ad_mail_admin',
                'passwordField' => 'mdp_admin',
                'idField' => 'id_administrateur'
            ],
            'createur' => [
                'model' => Createur::class,
                'emailField' => 'ad_mail_createur',
                'passwordField' => 'mdp_createur',
                'idField' => 'id_createur',
                'extraFields' => ['nom_createur']
            ]
        ];

        if (!isset($config[$userType])) {
            echo json_encode(['error' => 'Type d\'utilisateur non valide']);
            http_response_code(400);
            return;
        }

        $currentConfig = $config[$userType];
        
        // Validation des données
        $email = filter_var(trim($data[$currentConfig['emailField']] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = trim($data[$currentConfig['passwordField']] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Email non valide.']);
            return;
        }

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Les champs email et mot de passe sont requis.']);
            return;
        }

        // Recherche de l'utilisateur
        $modelClass = $currentConfig['model'];
        $user = $modelClass::getInstance()->findOneBy([$currentConfig['emailField'] => $email]);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => ucfirst($userType) . ' non trouvé.']);
            return;
        }

        // Vérification du mot de passe
        if (!password_verify($password, $user[$currentConfig['passwordField']])) {
            http_response_code(401);
            echo json_encode(['error' => 'Mot de passe incorrect.']);
            return;
        }

        // Génération du JWT
        $jwtSecret = $_ENV['JWT_SECRET'];
        if ($jwtSecret === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Clé secrète JWT manquante.']);
            return;
        }

        // Construction du payload
        $payload = [
            $currentConfig['idField'] => $user[$currentConfig['idField']],
            $currentConfig['emailField'] => $user[$currentConfig['emailField']],
            'userType' => $userType,
            'iat' => time(),
            'exp' => time() + 3600
        ];

        // Ajout des champs supplémentaires pour le créateur
        if ($userType === 'createur' && isset($currentConfig['extraFields'])) {
            foreach ($currentConfig['extraFields'] as $field) {
                $payload[$field] = $user[$field];
            }
        }

        try {
            $jwt = JWT::encode($payload, $jwtSecret, 'HS256');
            error_log("Token JWT généré : " . $jwt);
            http_response_code(200);
            echo json_encode(['token' => $jwt]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la génération du token.']);
        }
    }
    public function login()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'Méthode non autorisée.']);
            http_response_code(405);
            return;
        }

        // Charger les variables d'environnement
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');  
        $dotenv->load();

        // Récupérer les données JSON de la requête
        $data = json_decode(file_get_contents('php://input'), true);
        $email = filter_var(trim($data['ad_mail'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = trim($data['mdp'] ?? '');

        // Validation de l'email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Email non valide.']);
            http_response_code(400);
            return;
        }

        if (empty($email) || empty($password)) {
            echo json_encode(['error' => 'Les champs email et mot de passe sont requis.']);
            http_response_code(400);
            return;
        }

        // D'abord, essayer de récupérer un utilisateur avec cet email dans la table des créateurs
        $createur = Createur::getInstance()->findOneBy(['ad_mail_createur' => $email]);

        // Si ce n'est pas un créateur, essayer de récupérer un administrateur avec cet email
        if (!$createur) {
            $administrateur = Administrateur::getInstance()->findOneBy(['ad_mail_admin' => $email]);

            // Si l'administrateur n'existe pas non plus
            if (!$administrateur) {
                echo json_encode(['error' => 'Utilisateur non trouvé.']);
                http_response_code(404);
                return;
            }

            // Vérifier le mot de passe pour l'administrateur
            if (!password_verify($password, $administrateur['mdp_admin'])) {
                echo json_encode(['error' => 'Mot de passe incorrect pour l\'administrateur.']);
                http_response_code(401);
                return;
            }

            // Générer le token JWT pour l'administrateur
            $jwtSecret = $_ENV['JWT_SECRET'];
            if ($jwtSecret === false) {
                echo json_encode(['error' => 'Clé secrète JWT manquante.']);
                http_response_code(500);
                return;
            }

            $payload = [
                'id_administrateur' => $administrateur['id_administrateur'],
                'ad_mail_admin' => $administrateur['ad_mail_admin'],
                'userType' => 'administrateur',
                'iat' => time(),
                'exp' => time() + 3600
            ];

            try {
                $jwt = JWT::encode($payload, $jwtSecret, 'HS256');
                http_response_code(200);
                echo json_encode([
                    'token' => $jwt,
                ]);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erreur lors de la génération du token.']);
                http_response_code(500);
            }
            

            return;
        }

        // Si un créateur a été trouvé, vérifier le mot de passe
        if (!password_verify($password, $createur['mdp_createur'])) {
            echo json_encode(['error' => 'Mot de passe incorrect pour le créateur.']);
            http_response_code(401);
            return;
        }

        // Générer le token JWT pour le créateur
        $jwtSecret = $_ENV['JWT_SECRET'];
        if ($jwtSecret === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Clé secrète JWT manquante.']);
            return;
        }

        $payload = [
            'id_createur' => $createur['id_createur'],       
            'ad_mail_createur' => $createur['ad_mail_createur'], 
            'userType' => 'createur',                        
            'nom_createur' => $createur['nom_createur'],     
            'iat' => time(),                                 
            'exp' => time() + 3600                           
        ];

        try {
            $jwt = JWT::encode($payload, $jwtSecret, 'HS256');
            http_response_code(200);
            echo json_encode(['token' => $jwt]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la génération du token.']);
        }
    }
    protected function logout()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
    
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée.']);
            return;
        }
    
        // Extraire le token de l'en-tête Authorization
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(400);
            echo json_encode(['error' => 'Token non fourni ou invalide.']);
            return;
        }
    
        $token = $matches[1];
        $jwtSecret = $_ENV['JWT_SECRET'];
    
        try {
            // Décoder le token pour obtenir les informations utilisateur
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            $payload = (array)$decoded;
    
            // Vérification du type d'utilisateur en fonction des champs présents
            if (isset($payload['ad_mail_admin'])) {
                $message = 'Déconnexion réussie pour l\'administrateur.';
            } elseif (isset($payload['ad_mail_createur'])) {
                $message = 'Déconnexion réussie pour le créateur.';
            } else {
                $message = 'Déconnexion réussie pour un utilisateur non spécifié.';
            }
    
            // Répondre avec un message de succès
            http_response_code(200);
            echo json_encode(['message' => $message]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Token invalide ou expiré.']);
        }
    }
}