<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Createur;
use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;
class CreateurController extends Controller
{


    /**
     * Page d'accueil pour lister tous les createurs.
     * @route [get] /
     *
     */
    public function index()
    {
        // Vérifier si l'utilisateur (créateur) est connecté
        $isLoggedIn = isset($_SESSION['ad_mail_createur']);
        $nom_createur = $isLoggedIn ? $_SESSION['nom_createur'] : null;
        $this->display('createurs/index.html.twig', compact('isLoggedIn','nom_createur'));
    }
    

    /**
     * Afficher le formulaire de saisie d'un nouvel createur ou traiter les
     * données soumises présentent dans $_POST.
     * @route [get]  /createurs/ajouter
     * @route [post] /createurs/ajouter
     *
     */
    public function create()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupérer les données JSON de la requête
            $data = json_decode(file_get_contents('php://input'), true);
    
            $nom_createur = trim($data['nom_createur'] ?? '');
            $ad_mail_createur = filter_var(trim($data['ad_mail_createur'] ?? ''), FILTER_SANITIZE_EMAIL);
            $mdp_createur = trim($data['mdp_createur'] ?? '');
            $genre = trim($data['genre'] ?? '');
            $ddn = trim($data['ddn'] ?? '');
    
            // Valider les données
            if (!filter_var($ad_mail_createur, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email non valide.']);
                return;
            }
    
            if (empty($nom_createur) || empty($ad_mail_createur) || empty($mdp_createur) || empty($genre) || empty($ddn)) {
                http_response_code(400);
                echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.']);
                return;
            }
    
            $validGenres = ['Homme', 'Femme', 'Autres'];
            if (!in_array($genre, $validGenres)) {
                http_response_code(400);
                echo json_encode(['error' => 'Genre invalide.']);
                return;
            }
    
            if (!DateTime::createFromFormat('Y-m-d', $ddn)) {
                http_response_code(400);
                echo json_encode(['error' => 'La date de naissance doit être au format YYYY-MM-DD.']);
                return;
            }
    
            $existingCreator = Createur::getInstance()->findOneBy(['ad_mail_createur' => $ad_mail_createur]);
            if ($existingCreator) {
                http_response_code(409); // Conflit
                echo json_encode(['error' => "L'adresse e-mail est déjà utilisée."]);
                return;
            }
    
            $hashedPassword = password_hash($mdp_createur, PASSWORD_BCRYPT);
    
            try {
                $createurId = Createur::getInstance()->create([
                    'nom_createur' => $nom_createur,
                    'ad_mail_createur' => $ad_mail_createur,
                    'mdp_createur' => $hashedPassword,
                    'genre' => $genre,
                    'ddn' => $ddn,
                ]);
    
                http_response_code(201);
                echo json_encode(['message' => 'Créateur créé avec succès.', 'id' => $createurId]);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => "Erreur lors de la création."]);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée.']);
        }
    }
    
    
    
    
    public function login()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');  
            $dotenv->load();
            $data = json_decode(file_get_contents('php://input'), true);
    
            $ad_mail_createur = filter_var(trim($data['ad_mail_createur'] ?? ''), FILTER_SANITIZE_EMAIL);
            $mdp_createur = trim($data['mdp_createur'] ?? '');
    
            if (!filter_var($ad_mail_createur, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Email non valide.']);
                return;
            }
    
            if (empty($ad_mail_createur) || empty($mdp_createur)) {
                http_response_code(400);
                echo json_encode(['error' => 'Les champs email et mot de passe sont requis.']);
                return;
            }
    
            $createur = Createur::getInstance()->findOneBy(['ad_mail_createur' => $ad_mail_createur]);
    
            if (!$createur) {
                http_response_code(404); // Non trouvé
                echo json_encode(['error' => 'Créateur non trouvé.']);
                return;
            }
    
            if (!password_verify($mdp_createur, $createur['mdp_createur'])) {
                http_response_code(401); // Non autorisé
                echo json_encode(['error' => 'Mot de passe incorrect.']);
                return;
            }
    
            // Récupérer la clé secrète directement ici
            $jwtSecret = $_ENV['JWT_SECRET'];
            if ($jwtSecret === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Clé secrète JWT manquante.']);
                return;
            }
    
            // Générer le token JWT
            $payload = [
                'id_createur' => $createur['id_createur'],
                'ad_mail_createur' => $createur['ad_mail_createur'],
                'nom_createur' => $createur['nom_createur'],
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
        
        // Supprimer seulement l'ID et l'email du créateur
        unset($_SESSION['nom_createur'], $_SESSION['ad_mail_createur'], $_SESSION['id_createur']);

        // Rediriger vers la page d'accueil ou une autre page
        HTTP::redirect('/createurs');
    }

    public function edit(int|string $id)
    {
        // Forcer l'ID à être un entier si nécessaire
        $id = (int)$id;
    
        // Récupérer l'createur existant
        $createur = Createur::getInstance()->find($id);
    
        if ($this->isGetMethod()) {
            // Passer l'createur à la vue pour préremplir le formulaire
            $this->display('createurs/edit.html.twig', compact('createur'));
        } else {
            // Traiter la requête POST pour la mise à jour
    
            // 1. Préparer le nom du fichier s'il y a une nouvelle image
            $filename = $createur['illustration']; // garder l'image existante par défaut
    
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
                $destination = APP_ASSETS_DIRECTORY . 'image' . DS . 'createur' . DS . $filename;
                // déplacer le fichier dans son dossier cible
                move_uploaded_file($source, $destination);
            }
    
            // 2. Exécuter la requête de mise à jour dans la base de données
            Createur::getInstance()->update($id, [
                'email' => trim($_POST['email']),
                'mdp_createur' => trim($_POST['mdp_createur']),
                'display_name' => trim($_POST['display_name']),
                'illustration' => $filename, // utilise soit l'image existante, soit la nouvelle
            ]);
    
            // 3. Rediriger vers la page d'accueil après la mise à jour
            HTTP::redirect('/');
        }
    }
    
    /**
     * Effacer un createur.
     * @route [get] /createurs/effacer/{id}
     *
     */
    public function delete(
        int|string $id
    ) {
            // 1. Forcer l'ID à être un entier si nécessaire
    $id = (int) $id;

    // 2. Récupérer l'createur existant
    $createur = Createur::getInstance()->find($id);

    // 3. Vérifier si l'createur existe
    if (!$createur) {
        // Si l'createur n'existe pas, rediriger ou afficher un message d'erreur
        HTTP::redirect('/');
        return;
    }

    // 4. Supprimer l'image de l'createur s'il en a une
    if (!empty($createur['illustration'])) {
        $imagePath = APP_ASSETS_DIRECTORY . 'image' . DS . 'createur' . DS . $createur['illustration'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Supprimer l'image du serveur
        }
    }

    // 5. Supprimer l'createur de la base de données
    Createur::getInstance()->delete($id);

    // 6. Rediriger vers la page d'accueil après la suppression
    HTTP::redirect('/');
    }
}
