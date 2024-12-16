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
        // Vérifier si l'utilisateur est connecté
        $isLoggedIn = isset($_SESSION['ad_mail_admin']);
        $ad_mail_admin = $isLoggedIn ? $_SESSION['ad_mail_admin'] : null;
    
        // Récupérer les informations sur les decks
        $decks = Deck::getInstance()->findAll();
    
        // Vérifier si des decks ont été récupérés
        if ($decks) {
            // Définir l'en-tête Content-Type pour une réponse JSON
            header("Content-Type: application/json");
    
            // Retourner les decks sous forme de JSON
            echo json_encode([
                'status' => 'success',
                'decks' => $decks
            ]);
        } else {
            // Si aucune donnée n'est trouvée, retourner un message d'erreur
            header("Content-Type: application/json");
            echo json_encode([
                'status' => 'error',
                'message' => 'Aucun deck trouvé'
            ]);
        }
    }
    public function getPlayableDecks()
    {
        // Récupérer les decks jouables
        $decks = Deck::getInstance()->findPlayableDecks();
    
        // Vérifier si des decks ont été récupérés
        if ($decks) {
            // Définir l'en-tête Content-Type pour une réponse JSON
            header("Content-Type: application/json");
    
            // Retourner les decks sous forme de JSON
            echo json_encode([
                'status' => 'success',
                'decks' => $decks
            ]);
        } else {
            // Si aucune donnée n'est trouvée, retourner un message d'erreur
            header("Content-Type: application/json");
            echo json_encode([
                'status' => 'error',
                'message' => 'Aucun deck jouable trouvé'
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
    
        // Tableau pour stocker les logs à inclure dans la réponse
        $responseLogs = [];
    
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $responseLogs[] = "=== Début de la requête pour création de deck ===";
    
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
            $responseLogs[] = "JWT reçu : " . $jwt;
    
            $jwtSecret = $_ENV['JWT_SECRET'];
            $responseLogs[] = "JWT_SECRET : " . $jwtSecret;
    
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
    
            // Extraire l'ID de l'administrateur depuis le JWT
            $id_administrateur_from_token = $decoded->id_administrateur ?? null;
    
            // Récupérer les données JSON envoyées dans le corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
            $responseLogs[] = "Données reçues : " . json_encode($data);
    
            // Vérification des données envoyées
            if (!isset($data['titre_deck']) || !isset($data['date_fin_deck']) || !isset($data['nb_cartes']) || !isset($data['id_administrateur'])) {
                $responseLogs[] = "Champs obligatoires manquants.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.', 'logs' => $responseLogs]);
                return;
            }
    
            // Extraire les données de la requête
            $titre_deck = $data['titre_deck'];
            $date_fin_deck = $data['date_fin_deck'];
            $nb_cartes = $data['nb_cartes'];
            $id_administrateur_from_request = $data['id_administrateur'];  // L'ID administrateur envoyé par la requête
    
            $responseLogs[] = "Titre deck : $titre_deck, Date fin : $date_fin_deck, Nombre de cartes : $nb_cartes, ID administrateur envoyé : $id_administrateur_from_request";
    
            // Vérification que l'ID administrateur de la requête correspond à celui du token
            if ($id_administrateur_from_request != $id_administrateur_from_token) {
                $responseLogs[] = "ID administrateur invalide.";
                http_response_code(403); // Forbidden
                echo json_encode(['error' => 'ID administrateur invalide.', 'logs' => $responseLogs]);
                return;
            }
    
            // Validation de la date
            if (!DateTime::createFromFormat('Y-m-d', $date_fin_deck)) {
                $responseLogs[] = "Format de la date invalide.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Format de la date invalide. Utilisez le format YYYY-MM-DD.', 'logs' => $responseLogs]);
                return;
            }
    
            // Validation du nombre de cartes
            if (!is_numeric($nb_cartes) || intval($nb_cartes) <= 0) {
                $responseLogs[] = "Le nombre de cartes doit être un entier positif.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Le nombre de cartes doit être un entier positif.', 'logs' => $responseLogs]);
                return;
            }
    
            // Créer le nouveau deck
            $deckId = Deck::getInstance()->create([
                'titre_deck' => $titre_deck,
                'date_fin_deck' => $date_fin_deck,
                'nb_cartes' => intval($nb_cartes),
                'id_administrateur' => $id_administrateur_from_request, // Utilisation de l'ID administrateur envoyé
            ]);
    
            if ($deckId) {
                $responseLogs[] = "Deck créé avec succès. ID : $deckId";
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'deck' => [
                        'id_deck' => $deckId,
                        'titre_deck' => $titre_deck,
                        'date_fin_deck' => $date_fin_deck,
                        'nb_cartes' => $nb_cartes,
                        'id_administrateur' => $id_administrateur_from_request,
                    ],
                    'logs' => $responseLogs
                ]);
            } else {
                $responseLogs[] = "Erreur lors de la création du deck.";
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => 'Erreur lors de la création du deck.', 'logs' => $responseLogs]);
            }
        } else {
            $responseLogs[] = "Méthode non autorisée.";
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Méthode non autorisée.', 'logs' => $responseLogs]);
        }
    
        $responseLogs[] = "=== Fin de la requête ===";
    }
    
    
    public function update(int|string $id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
    
        $responseLogs = [];
    
        // Forcer l'ID à être un entier
        $id = (int)$id;
    
        // Récupérer le deck existant avec findOneBy
        $deck = Deck::getInstance()->findOneBy(['id_deck' => $id]);
        if (!$deck) {
            $responseLogs[] = "Deck introuvable avec l'ID : $id.";
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Deck introuvable.', 'logs' => $responseLogs]);
            return;
        }
    
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $responseLogs[] = "=== Début de la requête pour modification du deck ===";
    
            // Récupérer les données JSON envoyées dans le corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
            $responseLogs[] = "Données reçues : " . json_encode($data);
    
            // Vérifier les champs obligatoires
            if (!isset($data['titre_deck']) || !isset($data['date_fin_deck']) || !isset($data['nb_cartes'])) {
                $responseLogs[] = "Champs obligatoires manquants.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.', 'logs' => $responseLogs]);
                return;
            }
    
            // Extraire les données
            $titre_deck = $data['titre_deck'];
            $date_fin_deck = $data['date_fin_deck'];
            $nb_cartes = $data['nb_cartes'];
    
            // Valider la date
            if (!DateTime::createFromFormat('Y-m-d', $date_fin_deck)) {
                $responseLogs[] = "Format de la date invalide.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Format de la date invalide. Utilisez le format YYYY-MM-DD.', 'logs' => $responseLogs]);
                return;
            }
    
            // Valider le nombre de cartes
            if (!is_numeric($nb_cartes) || intval($nb_cartes) <= 0) {
                $responseLogs[] = "Le nombre de cartes doit être un entier positif.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Le nombre de cartes doit être un entier positif.', 'logs' => $responseLogs]);
                return;
            }
    
            // Mettre à jour le deck
            $updated = Deck::getInstance()->update($id, [
                'titre_deck' => $titre_deck,
                'date_fin_deck' => $date_fin_deck,
                'nb_cartes' => intval($nb_cartes),
            ]);
    
            if ($updated) {
                $responseLogs[] = "Deck mis à jour avec succès.";
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'deck' => [
                        'id_deck' => $id,
                        'titre_deck' => $titre_deck,
                        'date_fin_deck' => $date_fin_deck,
                        'nb_cartes' => $nb_cartes,
                    ],
                    'logs' => $responseLogs,
                ]);
            } else {
                $responseLogs[] = "Erreur lors de la mise à jour du deck.";
                http_response_code(500); // Internal Server Error
                echo json_encode(['error' => 'Erreur lors de la mise à jour du deck.', 'logs' => $responseLogs]);
            }
        } else {
            $responseLogs[] = "Méthode non autorisée.";
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'Méthode non autorisée.', 'logs' => $responseLogs]);
        }
    }
    
    
    
    
    public function delete(int|string $id)
    {
        // Définir les en-têtes pour une réponse JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
    
        $responseLogs = [];
    
        // Forcer l'ID à être un entier
        $id = (int) $id;
    
        // Vérifier si la carte existe
        $deck = Deck::getInstance()->findOneBy(['id_deck' => $id]);
        if (!$deck) {
            $responseLogs[] = "Carte introuvable avec l'ID : $id.";
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Deck introuvable.', 'logs' => $responseLogs]);
            return;
        }
    
        // Supprimer la carte
        try {
            Deck::getInstance()->delete(['id_deck' => $id]);
            $responseLogs[] = "Carte supprimée avec succès. ID : $id";
    
            // Envoyer une réponse JSON de succès
            http_response_code(200); // OK
            echo json_encode([
                'status' => 'success',
                'message' => "Carte avec l'ID $id supprimée avec succès.",
                'logs' => $responseLogs
            ]);
        } catch (Exception $e) {
            // Gérer les erreurs lors de la suppression
            $responseLogs[] = "Erreur lors de la suppression de la carte : " . $e->getMessage();
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Erreur lors de la suppression de la carte.', 'logs' => $responseLogs]);
        }
    }
    public function like(int|string $id_deck)
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json");
    $id_deck = (int) $id_deck;

    // 1. Vérifier que l'ID est valide et que le créateur existe
    $id_deck = Deck::getInstance()->findOneBy(['id_deck' => $id_deck]);

    if (!$id_deck) {
        // Si l'ID du deck est introuvable, renvoyer une erreur
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Deck non trouvé.']);
        return;
    }

    // 2. Incrémenter le champ 'like' du créateur
    try {
        // Incrémenter la valeur du champ like de +1
        $newLikeCount = $deck['like'] + 1;

        // Mettre à jour la valeur du champ 'like' dans la base de données
        Deck::getInstance()->update($id_deck, ['deck' => $newLikeCount]);

        // 3. Renvoyer la réponse JSON avec le nouveau compteur de 'like'
        http_response_code(200); // OK
        echo json_encode([
            'message' => 'Avertissement ajouté avec succès.',
            'id_createur' => $id_createur,
            'new_like_count' => $newLikeCount
        ]);
    } catch (Exception $e) {
        // Si une erreur se produit lors de la mise à jour, renvoyer une erreur 500
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Erreur lors de l\'ajout d\'un like.']);
    }
}
    
}
