<?php

declare(strict_types=1); // strict mode

namespace App\Controller;

use App\Helper\HTTP;
use App\Model\Post;

class PostController extends Controller
{
    /**
     * Page d'accueil pour lister tous les posts.
     * @route [get] /
     *
     */
    public function index()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        $posts = Post::getInstance()->findAll();
        
        // Encodez le tableau en JSON pour l'envoyer au client
        echo json_encode($posts);
    }
    public function show($id)
    {
        $id = (int)$id;

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
    
        // Récupérer le post avec l'ID fourni
        $post = Post::getInstance()->find($id);
    
        // Vérifier si le post existe
        if ($post) {
            // Encodez le post en JSON pour l'envoyer au client
            echo json_encode($post);
        } else {
            // Post non trouvé, retourner une erreur 404
            http_response_code(404);
            echo json_encode([
                'error' => 'Post not found',
            ]);
        }
    }
    

    /**
     * Afficher le formulaire de saisie d'un nouvel Post ou traiter les
     * données soumises présentent dans $_POST.
     * @route [get]  /posts/ajouter
     * @route [post] /posts/ajouter
     *
     */
    public function create()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
    
        if ($this->isPostMethod()) {
            // Récupérer les données du corps de la requête
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Vérification de la validité des données
            if (isset($data['title']) && isset($data['body']) && isset($data['user_id'])) {
                // Créer le post
                $post = Post::getInstance()->create([
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'user_id' => $data['user_id']
                ]);
    
                // Réponse avec le post créé
                http_response_code(201); // 201 Created
                echo json_encode($post);
            } else {
                // Erreur : données manquantes
                http_response_code(400); // 400 Bad Request
                echo json_encode(['error' => 'Invalid input data']);
            }
        } else {
            // Si ce n'est pas une méthode POST
            http_response_code(405); // 405 Method Not Allowed
            echo json_encode(['error' => 'Method not allowed']);
        }
    }
    
    
    public function update(int|string $id)
    {
        // Vérifier que la méthode est PATCH
        if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
            // Si ce n'est pas une méthode PATCH, retourner une réponse JSON d'erreur
            http_response_code(405); // 405 Method Not Allowed
            echo json_encode(['error' => 'Method Not Allowed. Only PATCH is allowed.']);
            return;
        }
    
        // Forcer l'ID à être un entier si nécessaire
        $id = (int)$id;
    
        // Récupérer l'Post existant
        $post = Post::getInstance()->find($id);
    
        // Vérifier si le Post existe
        if (!$post) {
            // Si le Post n'existe pas, retourner une réponse JSON d'erreur
            http_response_code(404); // 404 Not Found
            echo json_encode(['error' => 'Post not found']);
            return;
        }
    
        // Récupérer les données du corps de la requête
        $data = json_decode(file_get_contents('php://input'), true);
    
        // Vérification des données reçues
        $fieldsToUpdate = [];
    
        if (isset($data['title'])) {
            $fieldsToUpdate['title'] = trim($data['title']);
        }
        if (isset($data['body'])) {
            $fieldsToUpdate['body'] = trim($data['body']);
        }
        if (isset($data['user_id'])) {
            $fieldsToUpdate['user_id'] = (int)$data['user_id'];
        }
    
        // Vérifier si des champs à mettre à jour sont fournis
        if (empty($fieldsToUpdate)) {
            http_response_code(400); // 400 Bad Request
            echo json_encode(['error' => 'No fields to update']);
            return;
        }
    
        // Exécuter la requête de mise à jour dans la base de données
        Post::getInstance()->update($id, $fieldsToUpdate);
    
        // Retourner une réponse JSON de succès
        http_response_code(200); // 200 OK
        echo json_encode(['message' => 'Post updated successfully']);
    }
    
    /**
     * Effacer un Post.
     * @route [get] /posts/effacer/{id}
     *
     */
    public function delete(int|string $id)
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: Content-Type");
        header("Content-Type: application/json");
        // 1. Vérifier que la méthode est DELETE
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            // Si ce n'est pas une méthode DELETE, retourner une réponse JSON d'erreur
            http_response_code(405); // 405 Method Not Allowed
            echo json_encode(['error' => 'Method Not Allowed. Only DELETE is allowed.']);
            return;
        }
    
        // 2. Forcer l'ID à être un entier si nécessaire
        $id = (int) $id;
    
        // 3. Récupérer le Post existant
        $post = Post::getInstance()->find($id);
    
        // 4. Vérifier si le Post existe
        if (!$post) {
            // Si le Post n'existe pas, retourner une réponse JSON d'erreur
            http_response_code(404); // 404 Not Found
            echo json_encode(['error' => 'Post not found']);
            return;
        }
    
        // 5. Supprimer le Post de la base de données
        Post::getInstance()->delete($id);
    
        // 6. Retourner une réponse JSON de succès
        http_response_code(200); // 200 OK
        echo json_encode(['message' => 'Post deleted successfully']);
    }
    
}    
