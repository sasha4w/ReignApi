<?php

declare(strict_types=1);

namespace App\Model;

class Carte extends Model
{
    use TraitInstance;

    protected $tableName = APP_TABLE_PREFIX . 'carte';

    public function existsForUserByRole(int $id_deck, int $userId, string $role): bool
    {
        try {
            // Déterminer la colonne à vérifier en fonction du rôle
            $column = $role === 'createur' ? 'id_createur' : 'id_administrateur';
            
            $sql = "SELECT * 
                    FROM {$this->tableName} 
                    WHERE id_deck = :id_deck 
                    AND {$column} = :userId";
    
            // Préparation de la requête
            $sth = $this->query($sql, [
                ':id_deck' => $id_deck,
                ':userId' => $userId
            ]);
    
            // Récupération du résultat
            $result = $sth->fetch();
    
            // Si une ligne est trouvée, l'utilisateur existe
            return $result !== false;
        } catch (\Exception $e) {
            // Gestion des erreurs
            error_log("Erreur dans existsForUserByRole: " . $e->getMessage());
            return false;
        }
    }

    
    
    public function findAllWithDecksCreateur(): array
    {
        $sql = "
            SELECT deck.*, COUNT(carte.id_carte) AS carte_count
            FROM " . APP_TABLE_PREFIX . "deck AS deck
            LEFT JOIN {$this->tableName} AS carte ON carte.id_deck = deck.id_deck
            GROUP BY deck.id_deck
            HAVING carte_count > 0
        ";
    
        $sth = $this->query($sql);
        return $sth->fetchAll();
    }
    public function getCreateursInfoForCartes(): array
    {
        $sql = "
            SELECT 
                c.id_carte,
                cr.id_createur,
                cr.nom_createur
            FROM {$this->tableName} AS c
            LEFT JOIN " . APP_TABLE_PREFIX . "createur AS cr 
                ON c.id_createur = cr.id_createur
        ";
        
        $sth = $this->query($sql);
        $result = $sth->fetchAll();
        
        // Organiser les résultats par id_carte
        $createursInfo = [];
        foreach ($result as $row) {
            $createursInfo[$row['id_carte']] = [
                'id_createur' => $row['id_createur'],
                'nom_createur' => $row['nom_createur']
            ];
        }
        
        return $createursInfo;
    }
    public function findAllWithDecksAdmin(): array
    {
        $sql = "
            SELECT deck.*, COUNT(carte.id_carte) AS carte_count
            FROM " . APP_TABLE_PREFIX . "deck AS deck
            LEFT JOIN {$this->tableName} AS carte ON carte.id_deck = deck.id_deck
            GROUP BY deck.id_deck
        ";
    
        $sth = $this->query($sql);
        return $sth->fetchAll();
    }
    public function findDeckById(int $id_deck): ?array
    {
        $sql = "SELECT * FROM " . APP_TABLE_PREFIX . "deck WHERE id_deck = :id_deck";
        $sth = $this->query($sql, [':id_deck' => $id_deck]);
        return $sth->fetch();
    }

    public function findByDeckAndCreateur(int $id_deck, int $id_createur): ?array
    {
        return $this->findAllBy(['id_deck' => $id_deck, 'id_createur' => $id_createur]);
    } 
    public function findByDeck(int $id_deck): array
    {
        return $this->findAllBy(['id_deck' => $id_deck]);
    } 
    public function findOneDeckById(int $id_deck): ?array
    {
        return $this->findOneBy(['id_deck' => $id_deck]);
    } 
    public function findByCreateur( int $id_createur): ?array
    {
        return $this->findAllBy(['id_createur' => $id_createur]);
    }     
    public function countByDeckId(int $id_deck): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->tableName} WHERE id_deck = :id_deck";
        $sth = $this->query($sql, [':id_deck' => $id_deck]);
        $result = $sth->fetch();
        return $result ? (int) $result['total'] : 0;
    }
   
    
    public function calculateNextOrder(int $id_deck): int
    {
        // Requête pour obtenir la plus grande valeur de ordre_soumission
        $sql = "SELECT MAX(ordre_soumission) AS max_order 
                FROM {$this->tableName} 
                WHERE id_deck = :id_deck";
    
        // Exécuter la requête
        $sth = $this->query($sql, [':id_deck' => $id_deck]);
        $result = $sth->fetch();
    
        // Retourner max_order + 1, ou 1 si aucune carte n'existe
        return $result && $result['max_order'] !== null ? (int) $result['max_order'] + 1 : 1;
    }
    public function findOrCreateRandomCard(int $deckId, int $creatorId): ?array
    {
        try {
            // Log des paramètres
            error_log("findOrCreateRandomCard - deckId: $deckId, creatorId: $creatorId");
    
            // Vérification de l'existence
            $sql = "SELECT c.* 
                    FROM `" . APP_TABLE_PREFIX . "carte_aleatoire` ca
                    JOIN `" . APP_TABLE_PREFIX . "carte` c ON ca.id_carte = c.id_carte
                    WHERE ca.id_deck = :id_deck 
                    AND ca.id_createur = :id_createur";
    
            $params = [
                ':id_deck' => $deckId,
                ':id_createur' => $creatorId
            ];
    
            error_log("SQL Vérification: " . $sql);
            error_log("Params: " . json_encode($params));
    
            $sth = $this->query($sql, $params);
            $carte = $sth->fetch();
    
            if (!$carte) {
                // Sélection nouvelle carte
                error_log("Aucune carte existante, sélection aléatoire");
                
                $sql = "SELECT * FROM `" . $this->tableName . "` 
                        WHERE id_deck = :id_deck 
                        ORDER BY RAND() LIMIT 1";
                        
                $sth = $this->query($sql, [':id_deck' => $deckId]);
                $carte = $sth->fetch();
    
                if ($carte) {
                    error_log("Nouvelle carte sélectionnée: " . $carte['id_carte']);
                    
                    // Insertion dans carte_aleatoire
                    $insertSql = "INSERT INTO `" . APP_TABLE_PREFIX . "carte_aleatoire` 
                                (id_deck, id_createur, id_carte) 
                                VALUES (:id_deck, :id_createur, :id_carte)";
                                
                    $this->query($insertSql, [
                        ':id_deck' => $deckId,
                        ':id_createur' => $creatorId,
                        ':id_carte' => $carte['id_carte']
                    ]);
                }
            }
    
            if ($carte) {
                // Décodage JSON sécurisé
                foreach (['valeurs_choix1', 'valeurs_choix2'] as $field) {
                    if (isset($carte[$field]) && !is_null($carte[$field])) {
                        $decoded = json_decode($carte[$field], true);
                        $carte[$field] = $decoded !== null ? $decoded : [];
                    } else {
                        $carte[$field] = [];
                    }
                }
            }
    
            return $carte ?: null;
    
        } catch (\Exception $e) {
            error_log("Erreur dans findOrCreateRandomCard: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    public function findRandomCarteForCreateur(int $id_deck, int $id_createur): ?array
    {
        try {
            // Log des paramètres
            error_log("findRandomCarteForCreateur - deckId: $id_deck, creatorId: $id_createur");
            
            // Sélectionner une carte aléatoire dans le deck pour ce créateur
            $sql = "SELECT c.* 
                    FROM `carte_aleatoire` ca
                    JOIN `carte` c ON ca.id_carte = c.id_carte
                    WHERE ca.id_deck = :id_deck 
                    AND ca.id_createur = :id_createur
                    ORDER BY RAND() LIMIT 1";
            
            $params = [
                ':id_deck' => $id_deck,
                ':id_createur' => $id_createur
            ];
            
            $sth = $this->query($sql, $params);
            $carte = $sth->fetch();
    
            if ($carte) {
                // Décodage des choix de la carte (si existants)
                foreach (['valeurs_choix1', 'valeurs_choix2'] as $field) {
                    if (!empty($carte[$field])) {
                        $decoded = json_decode($carte[$field], true);
                        $carte[$field] = is_array($decoded) ? $decoded : [];
                    } else {
                        $carte[$field] = [];
                    }
                }
            }
    
            return $carte ?: null;
    
        } catch (\Exception $e) {
            error_log("Erreur dans findRandomCarteForCreateur: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    
}