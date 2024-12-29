<?php

declare(strict_types=1);

namespace App\Model;

class Carte extends Model
{
    use TraitInstance;

    protected $tableName = APP_TABLE_PREFIX . 'carte';

    // public function findAllWithDecks(): array
    // {
    //     $sql = "
    //         SELECT *
    //         FROM " . APP_TABLE_PREFIX . "deck AS deck
    //         LEFT JOIN {$this->tableName} AS carte ON carte.id_deck = deck.id_deck
    //     ";
    
    //     $sth = $this->query($sql);
    //     return $sth->fetchAll();
    // }
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
    
    public function findByDeckAndCreateur(int $id_deck, int $id_createur): ?array
    {
        return $this->findAllBy(['id_deck' => $id_deck, 'id_createur' => $id_createur]);
    }
    
        public function findByDeck(int $id_deck): array
    {
        return $this->findAllBy(['id_deck' => $id_deck]);
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
    
    public function getOrAssignRandomCard(int $deckId, int $id_createur): ?array
    {
        // Vérifie si une carte aléatoire existe déjà pour ce deck et ce créateur dans `carte_aleatoire`
        $sql = "SELECT id_carte 
                FROM `carte_aleatoire` 
                WHERE id_deck = :id_deck AND id_createur = :id_createur"; // Ajout de la condition pour id_createur
        $sth = $this->query($sql, [
            ':id_deck' => $deckId,
            ':id_createur' => $id_createur
        ]);
        $carteId = $sth->fetchColumn();
    
        if ($carteId) {
            // Si une carte est déjà assignée, la récupérer
            $sql = "SELECT * FROM {$this->tableName} WHERE id_carte = :id_carte";
            $sth = $this->query($sql, [':id_carte' => $carteId]);
            $carte = $sth->fetch();
        } else {
            // Sinon, en tirer une au hasard parmi les cartes associées au deck et au créateur
            $sql = "SELECT * 
                    FROM {$this->tableName} 
                    WHERE id_deck = :id_deck AND id_createur = :id_createur 
                    ORDER BY RAND() LIMIT 1"; // Ajout de la condition pour id_createur
            $sth = $this->query($sql, [
                ':id_deck' => $deckId,
                ':id_createur' => $id_createur
            ]);
            $carte = $sth->fetch();
    
            if ($carte) {
                // Associer la carte tirée au deck et au créateur dans `carte_aleatoire`
                $insertSql = "INSERT INTO `carte_aleatoire` (id_deck, id_carte, id_createur) 
                              VALUES (:id_deck, :id_carte, :id_createur)";
                $this->query($insertSql, [
                    ':id_deck' => $deckId,
                    ':id_carte' => $carte['id_carte'],
                    ':id_createur' => $id_createur
                ]);
            }
        }
    
        // Décoder les valeurs de choix en JSON si une carte est trouvée
        if ($carte) {
            $carte['valeurs_choix1'] = json_decode($carte['valeurs_choix1'], true);
            $carte['valeurs_choix2'] = json_decode($carte['valeurs_choix2'], true);
        }
    
        return $carte ?: null;
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
    

    
}
