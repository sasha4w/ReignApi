<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Administrateur;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

class AdministrateurController extends Controller
{
    /**
     * Page d'accueil pour lister tous les administrateurs.
     * @route [get] /
     *
     */
    public function index()
    {        
        // Vérifier si l'utilisateur (administrateur) est connecté
        $isLoggedIn = isset($_SESSION['ad_mail_admin']);
        $ad_mail_admin = $isLoggedIn ? $_SESSION['ad_mail_admin'] : null;

        $this->display('administrateurs/index.html.twig', compact('isLoggedIn','ad_mail_admin'));

    }

    /**
     * Afficher le formulaire de saisie d'un nouvel administrateur ou traiter les
     * données soumises présentent dans $_POST.
     * @route [get]  /administrateurs/ajouter
     * @route [post] /administrateurs/ajouter
     *
     */
    public function create()
    {
        if ($this->isGetMethod()) {
            $this->display('administrateurs/create.html.twig');
        } else {

            // 2. exécuter la requête d'insertion
            Administrateur::getInstance()->create([
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'display_name' => trim($_POST['display_name']),
            ]);
            HTTP::redirect('/');
        }
    }
    public function login()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Charger les variables d'environnement
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');  
            $dotenv->load();
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Récupérer et nettoyer les données
            $ad_mail_admin = filter_var(trim($data['ad_mail_admin'] ?? ''), FILTER_SANITIZE_EMAIL);
            $mdp_admin = trim($data['mdp_admin'] ?? '');
    
            // Valider l'email
            if (!filter_var($ad_mail_admin, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email non valide.']);
                return;
            }
    
            // Vérifier si les champs obligatoires sont présents
            if (empty($ad_mail_admin) || empty($mdp_admin)) {
                http_response_code(400);
                echo json_encode(['error' => 'Les champs email et mot de passe sont requis.']);
                return;
            }
    
            // Rechercher l'administrateur par email
            $administrateur = Administrateur::getInstance()->findOneBy(['ad_mail_admin' => $ad_mail_admin]);
    
            if (!$administrateur) {
                http_response_code(404); // Non trouvé
                echo json_encode(['error' => 'Administrateur non trouvé.']);
                return;
            }
    
            // Vérifier le mot de passe
            if (!password_verify($mdp_admin, $administrateur['mdp_admin'])) {
                http_response_code(401); // Non autorisé
                echo json_encode(['error' => 'Mot de passe incorrect.']);
                return;
            }
    
            // Récupérer la clé secrète JWT
            $jwtSecret = $_ENV['JWT_SECRET'];
            if ($jwtSecret === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Clé secrète JWT manquante.']);
                return;
            }
    
            // Générer le token JWT
            $payload = [
                'id_administrateur' => $administrateur['id_administrateur'],
                'ad_mail_admin' => $administrateur['ad_mail_admin'],
                'iat' => time(),
                'exp' => time() + 3600, // Expire dans une heure
            ];
    
            try {
                $jwt = JWT::encode($payload, $jwtSecret, 'HS256');
    
                // Log du token généré pour le débogage
                error_log("Token JWT généré : " . $jwt);
    
                http_response_code(200);
                echo json_encode(['token' => $jwt]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la génération du token.']);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée.']);
        }
    }
    

    public function logout()
    {
        // Démarrer ou reprendre la session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Supprimer seulement l'ID et l'email du administrateur
        unset($_SESSION['ad_mail_admin'], $_SESSION['id_administrateur']);

        // Rediriger vers la page d'accueil ou une autre page
        HTTP::redirect('/administrateurs');
    }

    public function edit(int|string $id)
    {
        // Forcer l'ID à être un entier si nécessaire
        $id = (int)$id;
    
        // Récupérer l'administrateur existant
        $administrateur = Administrateur::getInstance()->find($id);
    
        if ($this->isGetMethod()) {
            // Passer l'administrateur à la vue pour préremplir le formulaire
            $this->display('administrateurs/edit.html.twig', compact('administrateur'));
        } else {
            // Traiter la requête POST pour la mise à jour
    
            // 1. Préparer le nom du fichier s'il y a une nouvelle image
            $filename = $administrateur['illustration']; // garder l'image existante par défaut
    
            // Vérifier si une nouvelle image a été envoyée
            if (!empty($_FILES['illustration']) && $_FILES['illustration']['type'] == 'image/webp') {
                // récupérer le nom et emplacement du fichier dans sa zone temporaire
                $source = $_FILES['illustration']['tmp_name'];
                // récupérer le nom originel du fichier
                $filename = $_FILES['illustration']['name'];
                // ajout d'un suffixe unique
                $filename_name = pathinfo($filename, PATHINFO_FILENAME);
                $filename_extension = pathinfo($filename, PATHINFO_EXTENSION);
                $suffix = uniqid();
                $filename = $filename_name . '_' . $suffix . '.' . $filename_extension;
                // construire le nom et l'emplacement du fichier de destination
                $destination = APP_ASSETS_DIRECTORY . 'image' . DS . 'administrateur' . DS . $filename;
                // déplacer le fichier dans son dossier cible
                move_uploaded_file($source, $destination);
            }
    
            // 2. Exécuter la requête de mise à jour dans la base de données
            Administrateur::getInstance()->update($id, [
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'display_name' => trim($_POST['display_name']),
                'illustration' => $filename, // utilise soit l'image existante, soit la nouvelle
            ]);
    
            // 3. Rediriger vers la page d'accueil après la mise à jour
            HTTP::redirect('/');
        }
    }
    
    /**
     * Effacer un administrateur.
     * @route [get] /administrateurs/effacer/{id}
     *
     */
    public function delete(
        int|string $id
    ) {
            // 1. Forcer l'ID à être un entier si nécessaire
    $id = (int) $id;

    // 2. Récupérer l'administrateur existant
    $administrateur = Administrateur::getInstance()->find($id);

    // 3. Vérifier si l'administrateur existe
    if (!$administrateur) {
        // Si l'administrateur n'existe pas, rediriger ou afficher un message d'erreur
        HTTP::redirect('/');
        return;
    }


    // 5. Supprimer l'administrateur de la base de données
    Administrateur::getInstance()->delete($id);

    // 6. Rediriger vers la page d'accueil après la suppression
    HTTP::redirect('/');
    }
}
