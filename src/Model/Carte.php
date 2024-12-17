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
    public function getOrAssignRandomCard(int $deckId): ?array
    {
        // Vérifie si une carte aléatoire existe déjà pour ce deck dans `carte aleatoire`
        $sql = "SELECT id_carte FROM `carte_aleatoire` WHERE id_deck = :id_deck"; // Utiliser le nom de la table avec des backticks
        $sth = $this->query($sql, [':id_deck' => $deckId]);
        $carteId = $sth->fetchColumn();
    
        if ($carteId) {
            // Si une carte est déjà assignée, la récupérer
            $sql = "SELECT * FROM {$this->tableName} WHERE id_carte = :id_carte";
            $sth = $this->query($sql, [':id_carte' => $carteId]);
            $carte = $sth->fetch();
        } else {
            // Sinon, en tirer une au hasard
            $sql = "SELECT * FROM {$this->tableName} WHERE id_deck = :id_deck ORDER BY RAND() LIMIT 1";
            $sth = $this->query($sql, [':id_deck' => $deckId]);
            $carte = $sth->fetch();
    
            if ($carte) {
                // Associer la carte tirée au deck dans `carte aleatoire`
                $insertSql = "INSERT INTO `carte_aleatoire` (id_deck, id_carte) VALUES (:id_deck, :id_carte)"; // Utiliser le nom de la table avec des backticks
                $this->query($insertSql, [':id_deck' => $deckId, ':id_carte' => $carte['id_carte']]);
            }
        }
    
        // Décoder les valeurs de choix en JSON si une carte est trouvée
        if ($carte) {
            $carte['valeurs_choix1'] = json_decode($carte['valeurs_choix1'], true);
            $carte['valeurs_choix2'] = json_decode($carte['valeurs_choix2'], true);
        }
    
        return $carte ?: null;
    }
    
    
}
