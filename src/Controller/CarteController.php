<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Carte;
use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

class CarteController extends Controller
{
    /**
     * Page d'accueil pour lister tous les cartes.
     * @route [get] /
     *
     */
    public function index()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        
        // Détecter le rôle de l'utilisateur
        $isLoggedInAsAdmin = isset($_SESSION['ad_mail_admin']);
        $isLoggedInAsCreateur = isset($_SESSION['ad_mail_createur']);
        
        // Initialiser les variables communes
        $decksInfos = [];
        $cartesByDeck = [];
        $currentTime = (new DateTime())->format('Y-m-d H:i:s');

        if ($isLoggedInAsAdmin) {
            $cartesByDeck = $this->getCartesForAdmin(); // Appel à la méthode dédiée
        }

        if ($isLoggedInAsCreateur) {
            $cartesByDeck = $this->getCartesForCreateur(); // Appel à la méthode dédiée
        }

        $this->display('cartes/index.html.twig', compact(
            'cartesByDeck', 
            'isLoggedInAsAdmin', 
            'isLoggedInAsCreateur', 
            'currentTime'
        ));
    }

    /**
     * Renvoie les cartes pour un administrateur, regroupées par deck.
     */
    public function getCartesForAdmin(): void
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
    
        try {
            $decksInfos = Carte::getInstance()->findAllWithDecksAdmin(); // All decks
            $cartes = Carte::getInstance()->findAll(); // All cards
            $this->decodeCardChoices($cartes); // Decode choices
    
            $cartesByDeck = $this->groupCartesByDeck($decksInfos, $cartes);
    
            // Return JSON response
            echo json_encode([
                'status' => 'success',
                'deck' => $cartesByDeck
            ], JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            // Return error response
            http_response_code(500); // Internal Server Error
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    

    /**
     * Renvoie les cartes pour un créateur spécifique, regroupées par deck.
     */
    public function getCartesForCreateur($id_createur): void
    {
        // Force la conversion du paramètre en entier
        $id_createur = (int) $id_createur;
    
        // Le reste de ta logique
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        
        // Tu utilises maintenant l'ID passé dans l'URL au lieu de la variable de session
        $decksInfos = Carte::getInstance()->findAllWithDecksCreateur(); // Decks du créateur
        $cartesByDeck = [];
    
        foreach ($decksInfos as $deckInfo) {
            $deckId = (int)($deckInfo['id_deck'] ?? $deckInfo->id_deck);
            $cartesByDeck[$deckId] = Carte::getInstance()->findByDeckAndCreateur($deckId, $id_createur);
            $this->decodeCardChoices($cartesByDeck[$deckId]);
        }
    
        // Return JSON response
        echo json_encode([
            'status' => 'success',
            'deck' => $cartesByDeck
        ], JSON_PRETTY_PRINT);
    }
    
    
    

    /**
     * Récupère les decks pour l'administrateur.
     */
    private function getDecksForAdmin(): array
    {
        return Carte::getInstance()->findAllWithDecksAdmin();
    }

    /**
     * Récupère les decks pour un créateur spécifique.
     */
    private function getDecksForCreateur(int $id_createur): array
    {
        return Carte::getInstance()->findAllWithDecksCreateur();
    }

    /**
     * Groupe les cartes par deck pour un administrateur.
     */
    private function getCartesGroupedByDeckForAdmin(array $decksInfos): array
    {
        $cartes = Carte::getInstance()->findAll(); // Récupérer toutes les cartes
        $this->decodeCardChoices($cartes); // Décoder les choix JSON
        return $this->groupCartesByDeck($decksInfos, $cartes);
    }

    /**
     * Groupe les cartes par deck pour un créateur spécifique.
     */
    private function getCartesGroupedByDeckForCreateur(array $decksInfos, int $id_createur): array
    {
        $cartesByDeck = [];
        foreach ($decksInfos as $deckInfo) {
            $deckId = (int)($deckInfo['id_deck'] ?? $deckInfo->id_deck);
            $cartesByDeck[$deckId] = Carte::getInstance()->findByDeckAndCreateur($deckId, $id_createur);
            $this->decodeCardChoices($cartesByDeck[$deckId]);
        }
        return $cartesByDeck;
    }

    /**
     * Décodage des choix JSON des cartes (modification directe sur la référence).
     */
    private function decodeCardChoices(array &$cartes): void
    {
        foreach ($cartes as &$carte) {
            if (is_array($carte)) {
                $carte['valeurs_choix1'] = json_decode($carte['valeurs_choix1'], true);
                $carte['valeurs_choix2'] = json_decode($carte['valeurs_choix2'], true);
            } elseif (is_object($carte)) {
                $carte->valeurs_choix1 = json_decode($carte->valeurs_choix1, true);
                $carte->valeurs_choix2 = json_decode($carte->valeurs_choix2, true);
            }
        }
    }

    /**
     * Regroupe les cartes par deck.
     */
    private function groupCartesByDeck(array $decksInfos, array $cartes): array
    {
        $cartesByDeck = [];
        foreach ($decksInfos as $deckInfo) {
            $deckId = (int)($deckInfo['id_deck'] ?? $deckInfo->id_deck);
            $cartesByDeck[$deckId] = array_filter($cartes, function ($carte) use ($deckId) {
                return (is_object($carte) ? $carte->id_deck : $carte['id_deck']) == $deckId;
            });

            // Récupérer et assigner la carte aléatoire
            $carteAleatoire = Carte::getInstance()->getOrAssignRandomCard($deckId);
            foreach ($cartesByDeck[$deckId] as &$carte) {
                if (is_object($carte)) {
                    $carte->carteAleatoire = $carteAleatoire;
                } else {
                    $carte['carteAleatoire'] = $carteAleatoire;
                }
            }
        }
        return $cartesByDeck;
    }
    
    

    /**
     * Afficher le formulaire de saisie d'un nouvel carte ou traiter les
     * données soumises présentent dans $_POST.
     * @route [get]  /cartes/ajouter
     * @route [post] /cartes/ajouter
     *
     */
    public function create()
    {
        // Définir les en-têtes pour une réponse JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
    
        $responseLogs = [];
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $responseLogs[] = "=== Début de la requête pour création de carte ===";
    
            // Charger les variables d'environnement
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
    
            // Récupérer le token depuis l'en-tête Authorization
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            $responseLogs[] = "Authorization Header: " . $authHeader;
    
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $responseLogs[] = "Token manquant ou invalide.";
                http_response_code(401); // Unauthorized
                echo json_encode(['error' => 'Token manquant ou invalide.', 'logs' => $responseLogs]);
                return;
            }
    
            $jwt = $matches[1]; // Le token JWT
            $jwtSecret = $_ENV['JWT_SECRET'];
    
            try {
                // Décoder le token
                $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
                $responseLogs[] = "JWT décodé : " . json_encode($decoded);
            } catch (Exception $e) {
                $responseLogs[] = "Erreur lors du décodage du token : " . $e->getMessage();
                http_response_code(401); // Unauthorized
                echo json_encode(['error' => 'Token invalide : ' . $e->getMessage(), 'logs' => $responseLogs]);
                return;
            }
    
            // Extraire les informations nécessaires du token
            $id_createur_from_token = $decoded->id_createur ?? null;
            $id_administrateur_from_token = $decoded->id_administrateur ?? null;
    
            // Vérifier qu'un administrateur ou créateur est authentifié
            if (!$id_createur_from_token && !$id_administrateur_from_token) {
                $responseLogs[] = "Aucun administrateur ou créateur identifié dans le token.";
                http_response_code(403); // Forbidden
                echo json_encode(['error' => 'Vous devez être connecté en tant qu’administrateur ou créateur.', 'logs' => $responseLogs]);
                return;
            }
    
            // Récupérer les données JSON envoyées dans le corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
            $responseLogs[] = "Données reçues : " . json_encode($data);
    
            // Validation des données obligatoires
            if (!isset($data['texte_carte']) || 
                !isset($data['valeurs_choix1_population']) || 
                !isset($data['valeurs_choix1_finances']) || 
                !isset($data['valeurs_choix2_population']) || 
                !isset($data['valeurs_choix2_finances']) || 
                !isset($data['ordre_soumission']) || 
                !isset($data['deck_id'])) {
                $responseLogs[] = "Champs obligatoires manquants.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.', 'logs' => $responseLogs]);
                return;
            }
    
            // Extraction des données
            $texte_carte = trim($data['texte_carte']);
            $valeurs_choix1_population = (int) $data['valeurs_choix1_population'];
            $valeurs_choix1_finances = (int) $data['valeurs_choix1_finances'];
            $valeurs_choix2_population = (int) $data['valeurs_choix2_population'];
            $valeurs_choix2_finances = (int) $data['valeurs_choix2_finances'];
            $ordre_soumission = (int) $data['ordre_soumission'];
            $deck_id = (int) $data['deck_id'];
    
            // Vérifications supplémentaires
            if (strlen($texte_carte) < 50 || strlen($texte_carte) > 280) {
                $responseLogs[] = "Le texte de la carte doit contenir entre 50 et 280 caractères.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Le texte de la carte doit contenir entre 50 et 280 caractères.', 'logs' => $responseLogs]);
                return;
            }
    
            // Encoder les choix en JSON
            $valeurs_choix1 = json_encode([
                'population' => $valeurs_choix1_population,
                'finances' => $valeurs_choix1_finances
            ]);
            $valeurs_choix2 = json_encode([
                'population' => $valeurs_choix2_population,
                'finances' => $valeurs_choix2_finances
            ]);
    
            // Préparer les données pour l'insertion
            $cardData = [
                'texte_carte' => $texte_carte,
                'valeurs_choix1' => $valeurs_choix1,
                'valeurs_choix2' => $valeurs_choix2,
                'id_deck' => $deck_id,
                'ordre_soumission' => $ordre_soumission,
            ];
    
            // Associer l'utilisateur selon le token
            if ($id_createur_from_token) {
                $cardData['id_createur'] = $id_createur_from_token;
            }
            if ($id_administrateur_from_token) {
                $cardData['id_administrateur'] = $id_administrateur_from_token;
            }
    
            // Insérer la carte dans la base de données
            try {
                $carteId = Carte::getInstance()->create($cardData);
                $responseLogs[] = "Carte créée avec succès. ID : $carteId";
    
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'card' => [
                        'id_carte' => $carteId,
                        'texte_carte' => $texte_carte,
                        'id_deck' => $deck_id,
                        'ordre_soumission' => $ordre_soumission,
                    ],
                    'logs' => $responseLogs
                ]);
            } catch (Exception $e) {
                $responseLogs[] = "Erreur lors de la création de la carte : " . $e->getMessage();
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => 'Erreur lors de la création de la carte.', 'logs' => $responseLogs]);
            }
        } else {
            $responseLogs[] = "Méthode non autorisée.";
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Méthode non autorisée.', 'logs' => $responseLogs]);
        }
    
        $responseLogs[] = "=== Fin de la requête ===";
    }
    
    
    
    public function edit(int|string $id)
    {
        // Forcer l'ID à être un entier si nécessaire
        $id = (int)$id;
    
        // Récupérer l'carte existant
        $carte = Carte::getInstance()->find($id);
    
        if ($this->isGetMethod()) {
            // Passer l'carte à la vue pour préremplir le formulaire
            $this->display('cartes/edit.html.twig', compact('carte'));
        } else {
            // Traiter la requête POST pour la mise à jour
    
            // 1. Préparer le nom du fichier s'il y a une nouvelle image
            $filename = $carte['illustration']; // garder l'image existante par défaut
    
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
                $destination = APP_ASSETS_DIRECTORY . 'image' . DS . 'carte' . DS . $filename;
                // déplacer le fichier dans son dossier cible
                move_uploaded_file($source, $destination);
            }
    
            // 2. Exécuter la requête de mise à jour dans la base de données
            Carte::getInstance()->update($id, [
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
     * Effacer un carte.
     * @route [get] /cartes/effacer/{id}
     *
     */
    public function delete(
        int|string $id
    ) {
            // 1. Forcer l'ID à être un entier si nécessaire
    $id = (int) $id;

    // 2. Récupérer l'carte existant
    $carte = Carte::getInstance()->find($id);

    // 3. Vérifier si l'carte existe
    if (!$carte) {
        // Si l'carte n'existe pas, rediriger ou afficher un message d'erreur
        HTTP::redirect('/');
        return;
    }

    // 4. Supprimer l'image de l'carte s'il en a une
    if (!empty($carte['illustration'])) {
        $imagePath = APP_ASSETS_DIRECTORY . 'image' . DS . 'carte' . DS . $carte['illustration'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Supprimer l'image du serveur
        }
    }

    // 5. Supprimer l'carte de la base de données
    Carte::getInstance()->delete($id);

    // 6. Rediriger vers la page d'accueil après la suppression
    HTTP::redirect('/');
    }
}
