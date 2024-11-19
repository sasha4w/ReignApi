<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Helper\Security;
use App\Model\Createur;
use DateTime;

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
        if ($this->isGetMethod()) {
            $this->display('createurs/create.html.twig');
        } else {
            // Récupérer et nettoyer les données du formulaire
            $nom_createur = trim($_POST['nom_createur']);
            $ad_mail_createur = filter_var(trim($_POST['ad_mail_createur']), FILTER_SANITIZE_EMAIL);
            $mdp_createur = trim($_POST['mdp_createur']);
            $genre = trim($_POST['genre']);
            $ddn = trim($_POST['ddn']);
    
            // Valider l'email
            if (!filter_var($ad_mail_createur, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email non valide.';
                return $this->display('createurs/create.html.twig', compact('error'));
            }
    
            // Vérifier si les champs obligatoires sont présents
            if (empty($nom_createur) || empty($ad_mail_createur) || empty($mdp_createur) || empty($genre) || empty($ddn)) {
                $error = 'Tous les champs obligatoires doivent être remplis.';
                return $this->display('createurs/create.html.twig', compact('error'));
            }
    
            // Valider le genre et la date de naissance
            $validGenres = ['Homme', 'Femme', 'Autres'];
            if (!in_array($genre, $validGenres)) {
                $error = 'Le genre sélectionné est invalide.';
                return $this->display('createurs/create.html.twig', compact('error'));
            }
    
            if (!DateTime::createFromFormat('Y-m-d', $ddn)) {
                $error = 'La date de naissance doit être au format YYYY-MM-DD.';
                return $this->display('createurs/create.html.twig', compact('error'));
            }
    
            // Vérifier l'unicité de l'email
            $existingCreator = Createur::getInstance()->findOneBy(['ad_mail_createur' => $ad_mail_createur]);
            if ($existingCreator) {
                $error = "L'adresse e-mail est déjà utilisée.";
                return $this->display('createurs/create.html.twig', compact('error'));
            }
    
            // Hacher le mot de passe avant de l'enregistrer
            $hashedPassword = password_hash($mdp_createur, PASSWORD_BCRYPT);
    
            try {
                // Insérer les données dans la base de données
                Createur::getInstance()->create([
                    'nom_createur' => $nom_createur,
                    'ad_mail_createur' => $ad_mail_createur,
                    'mdp_createur' => $hashedPassword,
                    'genre' => $genre,
                    'ddn' => $ddn,
                ]);
    
                // Redirection après succès
                HTTP::redirect('/createurs');
    
            } catch (PDOException $e) {
                // En cas d'autre erreur SQL
                $error = "Une erreur s'est produite lors de l'inscription.";
                return $this->display('createurs/create.html.twig', compact('error'));
            }
        }
    }
    
    
    
    public function login()
    {
        if ($this->isGetMethod()) {
            $this->display('createurs/login.html.twig');
        } else {
            // Récupérer et nettoyer les données du formulaire
            $nom_createur = trim($_POST['nom_createur']);
            $ad_mail_createur = filter_var(trim($_POST['ad_mail_createur']), FILTER_SANITIZE_EMAIL);
            $mdp_createur = trim($_POST['mdp_createur']);
    
            // Valider l'email
            if (!filter_var($ad_mail_createur, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email non valide';
                return $this->display('createurs/login.html.twig', compact('error'));
            }
    
            // Vérifier si les champs obligatoires sont présents
            if (empty($ad_mail_createur) || empty($mdp_createur) || empty($nom_createur)) {
                $error = 'Tous les champs obligatoires doivent être remplis.';
                return $this->display('createurs/login.html.twig', compact('error'));
            }
    
            // Récupérer le créateur à partir de l'email
            $createur = Createur::getInstance()->findOneBy(['ad_mail_createur' => $ad_mail_createur]);

            if (!$createur) {
                // Si le créateur n'existe pas, afficher une erreur
                $error = 'Créateur non trouvé.';
                return $this->display('createurs/login.html.twig', compact('error'));
            }
    
            // Vérifier le mot de passe
            if (!password_verify($mdp_createur, $createur['mdp_createur'])) {
                // Si le mot de passe est incorrect, afficher une erreur
                $error = 'Mot de passe incorrect.';
                return $this->display('createurs/login.html.twig', compact('error'));
            }
    
            // Si tout est correct, démarrer une session pour l'utilisateur
            $_SESSION['ad_mail_createur'] = $createur['ad_mail_createur'];
            $_SESSION['nom_createur'] = $createur['nom_createur'];
            $_SESSION['id_createur'] = $createur['id_createur'];

            // Rediriger vers la page d'accueil ou une autre page
            HTTP::redirect('/createurs');
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
