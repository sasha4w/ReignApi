<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Deck;
use DateTime;

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
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
    
        // Vérifier si la requête est une méthode POST
        if ($this->isPostMethod()) {
            // Récupérer les données JSON envoyées dans le corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
    
            // Vérification des données envoyées
            if (!isset($data['titre_deck']) || !isset($data['date_fin_deck']) || !isset($data['nb_cartes'])) {
                // Si les champs obligatoires sont manquants
                http_response_code(400); // Code HTTP 400 Bad Request
                echo json_encode(['error' => 'Tous les champs obligatoires doivent être remplis.']);
                return;
            }
    
            // Extraire les données
            $titre_deck = $data['titre_deck'];
            $date_fin_deck = $data['date_fin_deck'];
            $nb_cartes = $data['nb_cartes'];
    
            // Validation de la date
            if (!DateTime::createFromFormat('Y-m-d', $date_fin_deck)) {
                // Si la date n'est pas valide
                http_response_code(400); // Code HTTP 400 Bad Request
                echo json_encode(['error' => 'Format de la date invalide. Utilisez le format YYYY-MM-DD.']);
                return;
            }
    
            // Validation du nombre de cartes
            if (!is_numeric($nb_cartes) || intval($nb_cartes) <= 0) {
                // Si le nombre de cartes n'est pas un nombre entier positif
                http_response_code(400); // Code HTTP 400 Bad Request
                echo json_encode(['error' => 'Le nombre de cartes doit être un entier positif.']);
                return;
            }
    
            // Récupérer l'ID de l'administrateur de la session (si disponible)
            if (!isset($_SESSION['id_administrateur'])) {
                http_response_code(401); // Code HTTP 401 Unauthorized
                echo json_encode(['error' => 'Utilisateur non authentifié.']);
                return;
            }
            $id_administrateur = $_SESSION['id_administrateur'];
    
            // 2. Exécuter la requête pour créer un nouveau deck
            $deckId = Deck::getInstance()->create([
                'titre_deck' => $titre_deck,
                'date_fin_deck' => $date_fin_deck,
                'nb_cartes' => intval($nb_cartes),
                'id_administrateur' => $id_administrateur,
            ]);
    
            // Si le deck a été créé avec succès, renvoyer les informations du deck créé
            if ($deckId) {
                http_response_code(201); // Code HTTP 201 Created
                echo json_encode([
                    'status' => 'success',
                    'deck' => [
                        'id_deck' => $deckId,
                        'titre_deck' => $titre_deck,
                        'date_fin_deck' => $date_fin_deck,
                        'nb_cartes' => $nb_cartes,
                        'id_administrateur' => $id_administrateur,
                    ]
                ]);
            } else {
                // Si la création échoue pour une raison quelconque
                http_response_code(500); // Code HTTP 500 Internal Server Error
                echo json_encode(['error' => 'Erreur lors de la création du deck.']);
            }
        } else {
            // Si ce n'est pas une méthode POST
            http_response_code(405); // Code HTTP 405 Method Not Allowed
            echo json_encode(['error' => 'Méthode non autorisée.']);
        }
    }
    
    public function edit(int|string $id)
    {
        // Forcer l'ID à être un entier si nécessaire
        $id = (int)$id;
    
        // Récupérer l'deck existant
        $deck = Deck::getInstance()->find($id);
    
        if ($this->isGetMethod()) {
            // Passer l'deck à la vue pour préremplir le formulaire
            $this->display('decks/edit.html.twig', compact('deck'));
        } else {
            // Traiter la requête POST pour la mise à jour
    
            // 1. Préparer le nom du fichier s'il y a une nouvelle image
            $filename = $deck['illustration']; // garder l'image existante par défaut
    
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
                $destination = APP_ASSETS_DIRECTORY . 'image' . DS . 'deck' . DS . $filename;
                // déplacer le fichier dans son dossier cible
                move_uploaded_file($source, $destination);
            }
    
            // 2. Exécuter la requête de mise à jour dans la base de données
            Deck::getInstance()->update($id, [
                'email' => trim($_POST['email']),
                'password' => trim($_POST['password']),
                'display_name' => trim($_POST['display_name']),
                'illustration' => $filename, // utilise soit l'image existante, soit la nouvelle
            ]);
    
            // 3. Rediriger vers la page d'accueil après la mise à jour
            HTTP::redirect('/');
        }
    }
    public function delete(
        int|string $id
    ) {
            // 1. Forcer l'ID à être un entier si nécessaire
    $id = (int) $id;

    // 2. Récupérer l'deck existant
    $deck = Deck::getInstance()->find($id);

    // 3. Vérifier si l'deck existe
    if (!$deck) {
        // Si l'deck n'existe pas, rediriger ou afficher un message d'erreur
        HTTP::redirect('/decks');
        return;
    }

    // 5. Supprimer l'deck de la base de données
    Deck::getInstance()->delete($id);

    // 6. Rediriger vers la page d'accueil après la suppression
    HTTP::redirect('/decks');
    }
}
