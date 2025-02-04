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

}