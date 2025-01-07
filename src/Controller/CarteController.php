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
     * Renvoie les cartes pour un administrateur, regroupées par deck.
     */
    public function getCartesForAdmin(): void
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json");

    try {
        // Récupérer les informations des decks
        $decksInfos = Carte::getInstance()->findAllWithDecksAdmin(); // All decks
        // Récupérer toutes les cartes
        $cartes = Carte::getInstance()->findAll(); // All cards
        // Décode les choix des cartes
        $this->decodeCardChoices($cartes);

        // Organiser les cartes par deck
                   $cartesByDeck = [];
    
                   foreach ($decksInfos as $deckInfo) {
                       $deckId = $deckInfo['id_deck'];
                       $cartesForDeck = [];
                       $nbCartes = 0;
           
                       // Récupérer les cartes liées au deck courant
                       foreach ($cartes as $carte) {
                           if ($carte['id_deck'] == $deckId) {
                               $cartesForDeck[] = $carte;
                               $nbCartes++; // Compte le nombre de cartes pour ce deck
                           }
                       }
           
                       // Ajouter les informations sur le deck dans la réponse
                       $cartesByDeck[] = [
                           'id_deck' => $deckId,
                           'titre_deck' => $deckInfo['titre_deck'], // Titre du deck
                           'nb_cartes' => $deckInfo['nb_cartes'], // nombre de cartes total
                           'nb_cartes_ajoutées' => $nbCartes, // Nombre de cartes ajoutées
                           'date_debut' => $deckInfo['date_debut'], // Date de début
                           'date_fin_deck' => $deckInfo['date_fin_deck'], // Date de fin du deck
                           'cartes' => $cartesForDeck // Cartes associées à ce deck
                       ];
                   }
        // Retourne la réponse JSON
        echo json_encode([
            'status' => 'success',
            'deck' => $cartesByDeck
        ], JSON_PRETTY_PRINT);
    } catch (Exception $e) {
        // En cas d'erreur, retourne une réponse d'erreur
        http_response_code(500); // Erreur interne du serveur
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
        
        // Définir les en-têtes pour la réponse JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        try {
            // Récupérer les informations des decks du créateur
            $decksInfos = Carte::getInstance()->findAllWithDecksCreateur(); // Decks du créateur
            
            // Organiser les cartes par deck
            $cartesByDeck = [];
            
            // Parcourir les decks
            foreach ($decksInfos as $deckInfo) {
                $id_deck = (int) $deckInfo['id_deck'];
                
                // Utiliser la méthode findByDeckAndCreateur pour récupérer les cartes du créateur dans ce deck
                $cartesForDeck = Carte::getInstance()->findByDeckAndCreateur($id_deck, $id_createur);
    
                // Décode les choix des cartes
                $this->decodeCardChoices($cartesForDeck);
    
                // Ajouter les informations sur le deck dans la réponse
                if (!empty($cartesForDeck)) {
                    // Ajouter les informations sur le deck dans la réponse
                    $cartesByDeck[] = [
                        'id_deck' => $id_deck,
                        'titre_deck' => $deckInfo['titre_deck'], // Titre du deck
                        'nb_cartes' => $deckInfo['nb_cartes'], // Nombre de cartes total
                        'date_debut' => $deckInfo['date_debut'], // Date de début
                        'date_fin_deck' => $deckInfo['date_fin_deck'], // Date de fin du deck
                        'cartes' => $cartesForDeck // Cartes associées à ce deck
                    ];
                }
            }
    
            // Retourner la réponse JSON
            echo json_encode([
                'status' => 'success',
                'deck' => $cartesByDeck
            ], JSON_PRETTY_PRINT);
    
        } catch (Exception $e) {
            // En cas d'erreur, retourner une réponse d'erreur
            http_response_code(500); // Erreur interne du serveur
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    

/**
 * Renvoie les cartes d'un deck spécifique.
 */
public function getCartesForOneDeck($id_deck): void
{
    // Assurer que l'ID du deck est bien un entier
    $id_deck = (int) $id_deck;

    // Définir les en-têtes pour une réponse JSON
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json");
    
    try {
        // Récupérer les informations du deck (titre, dates, nb_cartes, etc.)
        $deckInfo = Carte::getInstance()->findDeckById($id_deck);

        // Si aucune information sur le deck n'est trouvée, renvoyer un message d'erreur
        if (!$deckInfo) {
            http_response_code(404); // Not Found
            echo json_encode([
                'status' => 'error',
                'message' => 'Aucun deck trouvé avec cet ID.'
            ]);
            return;
        }

        // Utiliser la méthode findByDeck pour récupérer toutes les cartes du deck
        $cartes = Carte::getInstance()->findByDeck($id_deck);

        // Si aucune carte n'est trouvée, renvoyer un message d'erreur
        if (empty($cartes)) {
            http_response_code(404); // Not Found
            echo json_encode([
                'status' => 'error',
                'message' => 'Aucune carte trouvée pour ce deck.'
            ]);
            return;
        }

        // Décoder les choix des cartes (si nécessaire)
        $this->decodeCardChoices($cartes);

        // Renvoyer la réponse JSON avec les informations du deck et les cartes
        echo json_encode([
            'status' => 'success',
            'deck' => [
                'id_deck' => $deckInfo['id_deck'],
                'titre_deck' => $deckInfo['titre_deck'], // Titre du deck
                'nb_cartes' => $deckInfo['nb_cartes'], // Nombre total de cartes
                'date_debut' => $deckInfo['date_debut'], // Date de début
                'date_fin_deck' => $deckInfo['date_fin_deck'], // Date de fin du deck
                'cartes' => $cartes // Cartes associées à ce deck
            ]
        ], JSON_PRETTY_PRINT);

    } catch (Exception $e) {
        // En cas d'erreur, renvoyer un message d'erreur avec le code 500
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
public function createOrGetRandom($deck_id)
{
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json");

    $responseLogs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $responseLogs[] = "=== Début de la requête pour assigner une carte aléatoire ===";

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
            $decoded = JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
            $responseLogs[] = "JWT décodé : " . json_encode($decoded);
        } catch (Exception $e) {
            $responseLogs[] = "Erreur lors du décodage du token : " . $e->getMessage();
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Token invalide : ' . $e->getMessage(), 'logs' => $responseLogs]);
            return;
        }

        $id_createur_from_token = $decoded->id_createur ?? null;

        if (!$id_createur_from_token) {
            $responseLogs[] = "Aucun créateur identifié dans le token.";
            http_response_code(403); // Forbidden
            echo json_encode(['error' => 'Vous devez être connecté en tant que créateur.', 'logs' => $responseLogs]);
            return;
        }

        // Valider le `deck_id` extrait de l'URL
        if (!$deck_id) {
            $responseLogs[] = "Deck ID manquant dans l'URL.";
            http_response_code(400); // Bad Request
            echo json_encode(['error' => 'L\'identifiant du deck est requis.', 'logs' => $responseLogs]);
            return;
        }

        try {
            $carteAleatoire = Carte::getInstance()->getOrAssignRandomCard($deck_id, $id_createur_from_token);
            $responseLogs[] = "Carte aléatoire récupérée ou assignée : " . json_encode($carteAleatoire);

            if (!$carteAleatoire) {
                $responseLogs[] = "Aucune carte trouvée pour ce deck et ce créateur.";
                http_response_code(404); // Not Found
                echo json_encode(['error' => 'Aucune carte disponible pour ce deck.', 'logs' => $responseLogs]);
                return;
            }

            http_response_code(201); 
            echo json_encode([
                'status' => 'success',
                'random_card' => $carteAleatoire,
                'logs' => $responseLogs
            ]);
        } catch (Exception $e) {
            $responseLogs[] = "Erreur lors de l'assignation de la carte aléatoire : " . $e->getMessage();
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Erreur interne lors de l\'assignation de la carte aléatoire.', 'logs' => $responseLogs]);
        }
    } else {
        $responseLogs[] = "Méthode non autorisée.";
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Méthode non autorisée.', 'logs' => $responseLogs]);
    }

    $responseLogs[] = "=== Fin de la requête ===";
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
                !isset($data['valeurs_choix1_texte']) ||
                !isset($data['valeurs_choix1_population']) || 
                !isset($data['valeurs_choix1_finances']) ||
                !isset($data['valeurs_choix2_texte']) ||
                !isset($data['valeurs_choix2_population']) || 
                !isset($data['valeurs_choix2_finances']) || 
                !isset($data['deck_id'])) {
                $responseLogs[] = "Champs obligatoires manquants.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.', 'logs' => $responseLogs]);
                return;
            }
            
            // Extraction des données
            $texte_carte = trim($data['texte_carte']);
            $valeurs_choix1_texte = trim($data['valeurs_choix1_texte']);
            $valeurs_choix1_population = (int) $data['valeurs_choix1_population'];
            $valeurs_choix1_finances = (int) $data['valeurs_choix1_finances'];
            $valeurs_choix2_texte = trim($data['valeurs_choix2_texte']);
            $valeurs_choix2_population = (int) $data['valeurs_choix2_population'];
            $valeurs_choix2_finances = (int) $data['valeurs_choix2_finances'];
            $deck_id = (int) $data['deck_id'];
            // Calculer automatiquement l'ordre de soumission
            $ordre_soumission = Carte::getInstance()->calculateNextOrder($deck_id);
    
            // Vérifications supplémentaires
            if (strlen($texte_carte) < 50 || strlen($texte_carte) > 280) {
                $responseLogs[] = "Le texte de la carte doit contenir entre 50 et 280 caractères.";
                http_response_code(400); // Bad Request
                echo json_encode(['error' => 'Le texte de la carte doit contenir entre 50 et 280 caractères.', 'logs' => $responseLogs]);
                return;
            }
    
            // Encoder les choix en JSON
            $valeurs_choix1 = json_encode([
                'texte' => $valeurs_choix1_texte,
                'population' => $valeurs_choix1_population,
                'finances' => $valeurs_choix1_finances
            ], JSON_UNESCAPED_UNICODE);
            $valeurs_choix2 = json_encode([
                'texte' => $valeurs_choix2_texte,
                'population' => $valeurs_choix2_population,
                'finances' => $valeurs_choix2_finances
            ], JSON_UNESCAPED_UNICODE);
    
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
    
    
    public function update(int|string $id)
    {
        // Set response headers for JSON
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json");
                
        // Force the ID to be an integer
        $id = (int)$id;
        
        // Get the existing card data using the ID
        $carte = Carte::getInstance()->findOneBy(['id_carte' => $id]);
        if (!$carte) {
            echo json_encode(['error' => 'Carte introuvable.']);
            http_response_code(404); // Not Found
            return;
        }
    
        // Check if it's a PATCH request
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            
    
            // Retrieve JSON data from the request body
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Ensure the data is valid
            if (!$data) {
                echo json_encode(['error' => 'Requête invalide ou vide.']);
                http_response_code(400); // Bad Request
                return;
            }
    
            // Prepare fields for update dynamically
            $fieldsToUpdate = [];
    
            // Update only provided fields and validate them
            if (isset($data['texte_carte'])) {
                $texte_carte = trim($data['texte_carte']);
                if (strlen($texte_carte) < 50 || strlen($texte_carte) > 280) {
                    echo json_encode(['error' => 'Le texte de la carte doit contenir entre 50 et 280 caractères.']);
                    http_response_code(400); // Bad Request
                    return;
                }
                $fieldsToUpdate['texte_carte'] = $texte_carte;
            }
    
            if (isset($data['valeurs_choix1_texte']) || 
                isset($data['valeurs_choix1_population']) || 
                isset($data['valeurs_choix1_finances'])) {
                $valeurs_choix1 = [
                    'texte' => $data['valeurs_choix1_texte'] ?? $carte['valeurs_choix1']['texte'],
                    'population' => $data['valeurs_choix1_population'] ?? $carte['valeurs_choix1']['population'],
                    'finances' => $data['valeurs_choix1_finances'] ?? $carte['valeurs_choix1']['finances']
                ];
                $fieldsToUpdate['valeurs_choix1'] = json_encode($valeurs_choix1, JSON_UNESCAPED_UNICODE);
            }
    
            if (isset($data['valeurs_choix2_texte']) || 
                isset($data['valeurs_choix2_population']) || 
                isset($data['valeurs_choix2_finances'])) {
                $valeurs_choix2 = [
                    'texte' => $data['valeurs_choix2_texte'] ?? $carte['valeurs_choix2']['texte'],
                    'population' => $data['valeurs_choix2_population'] ?? $carte['valeurs_choix2']['population'],
                    'finances' => $data['valeurs_choix2_finances'] ?? $carte['valeurs_choix2']['finances']
                ];
                $fieldsToUpdate['valeurs_choix2'] = json_encode($valeurs_choix2, JSON_UNESCAPED_UNICODE);
            }
    
            // Check if there are fields to update
            if (empty($fieldsToUpdate)) {
                echo json_encode(['error' => 'Aucun champ à mettre à jour.']);
                http_response_code(400); // Bad Request
                return;
            }
    
            // Update the card in the database
            try {
                Carte::getInstance()->update($id, $fieldsToUpdate);
                $responseLogs[] = "Carte mise à jour avec succès. ID : $id";
    
                // Send the success response
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'updated_fields' => $fieldsToUpdate,
                ]);
            } catch (Exception $e) {
                echo json_encode(['error' => 'Erreur lors de la mise à jour de la carte.', 'logs' => $responseLogs]);
                http_response_code(500); // Internal Server Error
            }
        } else {
            // If the request method is not PATCH, return an error
            echo json_encode(['error' => 'Méthode non autorisée.']);
            http_response_code(405); // Method Not Allowed
        }
    
        
    }
    
    
    

/**
 * Supprimer une carte.
 * @route [delete] /cartes/effacer/{id}
 */
public function delete(int|string $carteId)
{
    // Définir les en-têtes pour une réponse JSON
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json");

    $responseLogs = [];

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $responseLogs[] = "=== Début de la requête pour suppression de carte ===";

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

        // Vérifier l'existence du carte avant de tenter la suppression
        $carte = Carte::getInstance()->findOneBy(['id_carte' => $carteId]);
        if (!$carte) {
            $responseLogs[] = "Carte non trouvé.";
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Carte non trouvé.', 'logs' => $responseLogs]);
            return;
        }

        // Supprimer le carte en utilisant la méthode delete avec un tableau de critères
        if (Carte::getInstance()->delete(['id_carte' => $carteId])) {
            $responseLogs[] = "Carte supprimé avec succès.";
            http_response_code(200); // OK
            echo json_encode([
                'status' => 'success',
                'message' => 'Carte supprimé avec succès.',
                'logs' => $responseLogs
            ]);
        } else {
            $responseLogs[] = "Erreur lors de la suppression du carte.";
            http_response_code(500); // Internal Server Error
            echo json_encode(['error' => 'Erreur lors de la suppression du carte.', 'logs' => $responseLogs]);
        }
    } else {
        $responseLogs[] = "Méthode non autorisée.";
        http_response_code(405); // Method Not Allowed
        echo json_encode(['error' => 'Méthode non autorisée.', 'logs' => $responseLogs]);
    }

    $responseLogs[] = "=== Fin de la requête ===";
}

}
