<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Carte;
use DateTime;

class CarteController extends Controller
{
    /**
     * Page d'accueil pour lister tous les cartes.
     * @route [get] /
     *
     */
    public function index()
    {
        // Détecter le rôle de l'utilisateur
        $isLoggedInAsAdmin = isset($_SESSION['ad_mail_admin']);
        $isLoggedInAsCreateur = isset($_SESSION['ad_mail_createur']);
        
        // Initialiser les variables communes
        $decksInfos = [];
        $cartesByDeck = [];
        $currentTime = (new DateTime())->format('Y-m-d H:i:s');

        if ($isLoggedInAsAdmin) {
            $decksInfos = $this->getDecksForAdmin();
            $cartesByDeck = $this->getCartesGroupedByDeckForAdmin($decksInfos);
        }

        if ($isLoggedInAsCreateur) {
            $id_createur = (int)$_SESSION['id_createur'];
            $decksInfos = $this->getDecksForCreateur($id_createur);
            $cartesByDeck = $this->getCartesGroupedByDeckForCreateur($decksInfos, $id_createur);
        }

        $this->display('cartes/index.html.twig', compact(
            'decksInfos', 
            'cartesByDeck', 
            'isLoggedInAsAdmin', 
            'isLoggedInAsCreateur', 
            'currentTime'
        ));
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
    public function create($deckId)
    {
        $deckId = (int) $deckId;
        $isLoggedInAsAdmin = isset($_SESSION['ad_mail_admin']);
        $isLoggedInAsCreateur = isset($_SESSION['ad_mail_createur']); 
        $carteAleatoire = Carte::getInstance()->getOrAssignRandomCard($deckId);    
        $ordre_soumission = $isLoggedInAsCreateur ? Carte::getInstance()->countByDeckId($deckId) + 1 : 0;
    
        // Vérifie si la méthode HTTP est GET pour afficher le formulaire
        if ($this->isGetMethod()) {
            $this->display('cartes/create.html.twig', compact('deckId', 'isLoggedInAsAdmin', 'isLoggedInAsCreateur', 'ordre_soumission', 'carteAleatoire'));
        } else {
            // Récupérer et nettoyer les données du formulaire
            $texte_carte = trim($_POST['texte_carte']);
            $valeurs_choix1_population = (int) trim($_POST['valeurs_choix1_population']);
            $valeurs_choix1_finances = (int) trim($_POST['valeurs_choix1_finances']);
            $valeurs_choix2_population = (int) trim($_POST['valeurs_choix2_population']);
            $valeurs_choix2_finances = (int) trim($_POST['valeurs_choix2_finances']);
            $ordre_soumission = trim($_POST['ordre_soumission']);
            
            // Initialiser un tableau d'erreurs
            $errors = [];
    
            // Valider les champs obligatoires
            if (empty($texte_carte) || empty($valeurs_choix1_population) || empty($valeurs_choix1_finances) || empty($valeurs_choix2_population) || empty($valeurs_choix2_finances) || empty($ordre_soumission)) {
                $errors[] = 'Tous les champs obligatoires doivent être remplis.';
            }
    
            // Vérification de la longueur du texte de la carte
            if (strlen($texte_carte) < 50 || strlen($texte_carte) > 280) {
                $errors[] = 'Le texte de la carte doit contenir entre 50 et 280 caractères.';
            }
    
            // S'il y a des erreurs, afficher le formulaire avec les messages d'erreur
            if (!empty($errors)) {
                $error = implode(' ', $errors); // Joindre les erreurs pour les afficher
                return $this->display('cartes/create.html.twig', compact('deckId', 'error', 'isLoggedInAsAdmin', 'isLoggedInAsCreateur', 'ordre_soumission', 'carteAleatoire'));
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
            $data = [
                'texte_carte' => $texte_carte,
                'valeurs_choix1' => $valeurs_choix1,
                'valeurs_choix2' => $valeurs_choix2,
                'id_deck' => $deckId, // clé étrangère associée au deck
                'ordre_soumission' => $ordre_soumission,
            ];
    
            // Ajouter les ID créateur ou administrateur si l'utilisateur est connecté
            if ($isLoggedInAsCreateur) {
                $data['id_createur'] = trim($_SESSION['id_createur']);
            }
    
            if ($isLoggedInAsAdmin) {
                $data['id_administrateur'] = trim($_SESSION['id_administrateur']);
            }
    
            // Insérer la carte dans la base de données
            Carte::getInstance()->create($data);
    
            // Rediriger vers la liste des cartes après l'insertion
            HTTP::redirect('/cartes');
        }
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
