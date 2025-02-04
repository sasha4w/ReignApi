<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Deck;
use DateTime;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

class DeckController extends Controller
{
    /**
     * Page d'accueil pour lister tous les decks.
     * @route [get] /
     *
     */
    public function getDecks()
    {
        // Définir les en-têtes pour une réponse JSON et CORS
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
    
        // Charger les variables d'environnement
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    
        try {
            // Récupérer le token depuis l'en-tête Authorization
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
    
            $id_createur_from_token = null;
            $id_administrateur_from_token = null;
            $jwtSecret = $_ENV['JWT_SECRET'];
    
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                // Si un token est présent, tenter de le décoder
                $jwt = $matches[1];
    
                try {
                    $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
                    $id_createur_from_token = $decoded->id_createur ?? null;
                    $id_administrateur_from_token = $decoded->id_administrateur ?? null;
                } catch (Exception $e) {
                    // Si le token est invalide, on ignore et traite comme un utilisateur non authentifié
                    error_log("Erreur JWT : " . $e->getMessage()); 
                }
            }
    
            // Si l'utilisateur est authentifié
            if ($id_createur_from_token || $id_administrateur_from_token) {
                $decks = $id_administrateur_from_token 
                    ? Deck::getInstance()->findAllGroupedByStatus() 
                    : Deck::getInstance()->findDeckUpdatable();
            } else {
                // Si pas de token ou invalide, renvoyer uniquement les decks "Playable"
                $decks = Deck::getInstance()->findPlayableDecks();
            }
    
            // Vérifier si des decks ont été trouvés
            if (empty($decks)) {
                throw new Exception("Aucun deck trouvé.");
            }
    
            // Retourner les decks
            echo json_encode([
                'status' => 'success',
                'decks' => $decks
            ]);
    
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    
    
    /**
     * Afficher le formulaire de saisie d'un nouvel deck ou traiter les
     * données soumises présentent dans $_POST.
     * @route [get]  /decks/ajouter
     * @route [post] /decks/ajouter
     *
     */
    public function create()
    {
        // Définir les en-têtes pour une réponse JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
    
    
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Méthode non autorisée.']);
            return;
        }
    
    
        // Charger les variables d'environnement
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    
        // Récupérer le token depuis l'en-tête Authorization
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
    
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Token manquant ou invalide.']);
            return;
        }
    
        $jwt = $matches[1]; // Le token JWT
    
        $jwtSecret = $_ENV['JWT_SECRET'];
    
        try {
            // Décoder le token
            $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
        } catch (Exception $e) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Token invalide : ' . $e->getMessage()]);
            return;
        }
    
        // Extraire l'ID de l'administrateur depuis le JWT
        $id_administrateur_from_token = $decoded->id_administrateur ?? null;
    
        if (!$id_administrateur_from_token) {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'ID administrateur manquant dans le token.']);
            return;
        }
    
        // Récupérer les données JSON envoyées dans le corps de la requête
        $data = json_decode(file_get_contents('php://input'), true);
    
        // Vérification des données envoyées
        if (!isset($data['titre_deck']) || !isset($data['date_debut']) || !isset($data['date_fin_deck']) || !isset($data['nb_cartes'])) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.']);
            return;
        }
    
        // Extraire les données de la requête
        $titre_deck = $data['titre_deck'];
        $nb_cartes = $data['nb_cartes'];
    
        // Validation et création des objets DateTime
        $dateDebut = DateTime::createFromFormat('Y-m-d', $data['date_debut']);
        $dateFin = DateTime::createFromFormat('Y-m-d', $data['date_fin_deck']);
    
        // Vérification si les dates sont valides
        if (!$dateDebut || !$dateFin) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Format de la date invalide. Utilisez le format YYYY-MM-DD.']);
            return;
        }
        $today = new DateTime();
        $today->setTime(0, 0); // Réinitialiser l'heure pour ne comparer que la date

        if ($dateDebut < $today) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'La date de début doit être postérieure à la date actuelle.']);
            return;
        }
        
        // Vérification que la date de début est inférieure à la date de fin
        if ($dateDebut >= $dateFin) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'La date de début doit être antérieure à la date de fin.']);
            return;
        }

        // Validation du chevauchement des dates
        if (Deck::getInstance()->checkDeckDateOverlap($dateDebut, $dateFin)) {
            http_response_code(400);
            echo json_encode(['error' => 'Les dates se chevauchent avec un deck existant.']);
            return;
        }
    
        // Validation du nombre de cartes
        if (!is_numeric($nb_cartes) || intval($nb_cartes) <= 0) {
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'Le nombre de cartes doit être un entier positif.']);
            return;
        }
    
        // Créer le nouveau deck
        $deckId = Deck::getInstance()->create([
            'titre_deck' => $titre_deck,
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin_deck' => $dateFin->format('Y-m-d'),
            'nb_cartes' => intval($nb_cartes),
            'id_administrateur' => $id_administrateur_from_token,
        ]);
    
        if ($deckId) {
            http_response_code(201); // Created
            echo json_encode([
                'status' => 'success',
                'deck' => [
                    'id_deck' => $deckId,
                    'titre_deck' => $titre_deck,
                    'date_debut' => $dateDebut->format('Y-m-d'),
                    'date_fin_deck' => $dateFin->format('Y-m-d'),
                    'nb_cartes' => $nb_cartes,
                    'id_administrateur' => $id_administrateur_from_token,
                ]
            ]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Erreur lors de la création du deck.']);
        }
    
    }
    
    
    
    
    public function update(int|string $id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
    
        // Forcer l'ID à être un entier
        $id = (int)$id;
    
        // Récupérer le deck existant avec findOneBy
        $deck = Deck::getInstance()->findOneBy(['id_deck' => $id]);
        if (!$deck) {
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Deck introuvable.']);
            return;
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    
            // Récupérer les données JSON envoyées dans le corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Vérifier les champs obligatoires
            if (!isset($data['titre_deck']) || !isset($data['date_fin_deck']) || !isset($data['nb_cartes'])) {
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.']);
                return;
            }
    
            // Extraire les données
            $titre_deck = $data['titre_deck'];
            $date_fin_deck = $data['date_fin_deck'];
            $nb_cartes = $data['nb_cartes'];
    
            // Récupérer la date de début du deck existant
            $dateDebut = DateTime::createFromFormat('Y-m-d', $deck['date_debut']);
            if (!$dateDebut) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur interne: date de début invalide.']);
                return;
            }
    
            // Vérifier que la date de fin est bien au format attendu
            $dateFin = DateTime::createFromFormat('Y-m-d', $date_fin_deck);
            if (!$dateFin) {
                http_response_code(400);
                echo json_encode(['error' => 'Format de la date invalide. Utilisez le format YYYY-MM-DD.']);
                return;
            }
    
            // Vérifier que la date de début est dans le futur
            $today = new DateTime();
            $today->setTime(0, 0); // Ignorer l'heure pour comparer uniquement les dates
            if ($dateDebut < $today) {
                http_response_code(400);
                echo json_encode(['error' => 'La date de début ne peut pas être dans le passé.']);
                return;
            }
    
            // Vérifier que la date de fin est postérieure à la date de début
            if ($dateFin <= $dateDebut) {
                http_response_code(400);
                echo json_encode(['error' => 'La date de fin doit être postérieure à la date de début.']);
                return;
            }
    
            // Vérifier que les dates ne se chevauchent pas avec un autre deck
            if (Deck::getInstance()->checkDeckDateOverlap($dateDebut, $dateFin, $id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Les dates se chevauchent avec un deck existant.']);
                return;
            }
    
            // Valider le nombre de cartes
            if (!is_numeric($nb_cartes) || intval($nb_cartes) <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Le nombre de cartes doit être un entier positif.']);
                return;
            }
    
            // Mettre à jour le deck
            $updated = Deck::getInstance()->update($id, [
                'titre_deck' => $titre_deck,
                'date_fin_deck' => $date_fin_deck,
                'nb_cartes' => intval($nb_cartes),
            ]);
    
            if ($updated) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'deck' => [
                        'id_deck' => $id,
                        'titre_deck' => $titre_deck,
                        'date_debut' => $dateDebut->format('Y-m-d'), // Garder l'info utile
                        'date_fin_deck' => $dateFin->format('Y-m-d'),
                        'nb_cartes' => $nb_cartes,
                    ]
                ]);
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => 'Erreur lors de la mise à jour du deck.']);
            }
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Méthode non autorisée.']);
        }
    }
    
    public function updateDeckStatus($id_deck)  // L'id_deck est maintenant passé en paramètre
    {
        // Définir les en-têtes pour une réponse JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Methods: PATCH");
        header("Content-Type: application/json");
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            // Charger les variables d'environnement
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
            // Récupérer le token depuis l'en-tête Authorization
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                http_response_code(401); // Unauthorized
                echo json_encode(['error' => 'Token manquant ou invalide.']);
                return;
            }
            $jwt = $matches[1]; // Le token JWT
            $jwtSecret = $_ENV['JWT_SECRET'];
            try {
                // Décoder le token
                $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
            } catch (Exception $e) {
                http_response_code(401); // Unauthorized
                echo json_encode(['error' => 'Token invalide : ' . $e->getMessage()]);
                return;
            }
            // Extraire les informations nécessaires du token
            $id_administrateur_from_token = $decoded->id_administrateur ?? null;
            // Vérifier que c'est un administrateur
            if (!$id_administrateur_from_token) {
                http_response_code(403); // Forbidden
                echo json_encode(['error' => 'Vous devez être administrateur pour modifier le status.']);
                return;
            }
            // Récupérer le status depuis le body
            $data = json_decode(file_get_contents('php://input'), true);
           
            if (!isset($data['status'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Le status est requis']);
                return;
            }
            try {
                // Mettre à jour le status
                $result = Deck::getInstance()->updateStatus(
                    intval($id_deck),  // On utilise l'id_deck de l'URL
                    $data['status']
                );
                if ($result === true) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Status du deck mis à jour'
                    ]);
                } elseif ($result === false) {
                    http_response_code(400); // Bad Request
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Le statut est déjà celui demandé'
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Deck non trouvé'
                    ]);
                }
            } catch (Exception $e) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode([
                'error' => 'Méthode non autorisée'
            ]);
        }
    }
        
    
    
    public function delete(int|string $deckId)
    {
        // Définir les en-têtes pour une réponse JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
        
        // Vérifier que la méthode est DELETE
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {    
            // Charger les variables d'environnement
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
    
            // Récupérer le token depuis l'en-tête Authorization
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
    
            // Vérifier que le token est présent
            if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                http_response_code(401); // Unauthorized
                echo json_encode(['error' => 'Token manquant ou invalide.']);
                return;
            }
    
            $jwt = $matches[1]; // Le token JWT
    
            $jwtSecret = $_ENV['JWT_SECRET'];
    
            try {
                // Décoder le token
                $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
            } catch (Exception $e) {
                http_response_code(401); // Unauthorized
                echo json_encode(['error' => 'Token invalide : ' . $e->getMessage()]);
                return;
            }
    
            // Vérifier l'existence du deck avant de tenter la suppression
            $deck = Deck::getInstance()->findOneBy(['id_deck' => $deckId]);
            if (!$deck) {
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Deck non trouvé.']);
                return;
            }
    
            // Supprimer le deck
            // Utilisation de delete avec un tableau de critères pour rendre ça plus flexible
            if (Deck::getInstance()->delete(['id_deck' => $deckId])) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Deck supprimé avec succès.'
                ]);
            } else {
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => 'Erreur lors de la suppression du deck.']);
            }
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Méthode non autorisée.']);
        }
    
    }
    
    
    public function like(int|string $id_deck)
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json");
    $id_deck = (int) $id_deck;

    // 1. Vérifier que l'ID est valide et que le créateur existe
    $deck = Deck::getInstance()->findOneBy(['id_deck' => $id_deck]);

    if (!$id_deck) {
        // Si l'ID du deck est introuvable, renvoyer une erreur
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Deck non trouvé.']);
        return;
    }

    // 2. Incrémenter le champ 'like' du créateur
    try {
        // Incrémenter la valeur du champ like de +1
        $newLikeCount = $deck['nb_jaime'] + 1;

        // Mettre à jour la valeur du champ 'like' dans la base de données
        Deck::getInstance()->update($id_deck, ['nb_jaime' => $newLikeCount]);

        // 3. Renvoyer la réponse JSON avec le nouveau compteur de 'like'
        http_response_code(200); // OK
        echo json_encode([
            'message' => 'Avertissement ajouté avec succès.',
            'id_deck' => $id_deck,
            'new_nb_jaime' => $newLikeCount
        ]);
    } catch (Exception $e) {
        // Si une erreur se produit lors de la mise à jour, renvoyer une erreur 500
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Erreur lors de l\'ajout d\'un like.']);
    }
}
    
}
