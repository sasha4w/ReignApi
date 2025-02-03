<?php

declare(strict_types=1);

namespace App\Model;
use DateTime;


class Deck extends Model
{
    use TraitInstance;

    protected $tableName = APP_TABLE_PREFIX . 'deck';
    public function findPlayableDecks(): array
    {
        $sql = "
            SELECT 
                deck.id_deck, 
                deck.titre_deck, 
                deck.date_debut, 
                deck.date_fin_deck, 
                deck.nb_jaime,
                deck.status,      
                deck.nb_cartes,
                COUNT(carte.id_carte) AS nb_cartes_atm
            FROM 
                `{$this->tableName}` AS deck
            LEFT JOIN 
                carte AS carte ON deck.id_deck = carte.id_deck
            WHERE 
                deck.nb_cartes = (
                    SELECT COUNT(*) 
                    FROM carte 
                    WHERE carte.id_deck = deck.id_deck
                )
                OR NOW() > deck.date_fin_deck
            GROUP BY 
                deck.id_deck
        ";
    
        $sth = $this->query($sql);
        return $sth->fetchAll();
    }
    
public function findAllWithCardCount(): array
{
    $sql = "
        SELECT 
            deck.id_deck, 
            deck.titre_deck, 
            deck.date_debut, 
            deck.date_fin_deck, 
            deck.nb_cartes, 
            COUNT(carte.id_carte) AS nb_cartes_atm, 
            deck.nb_jaime, 
            deck.id_administrateur
        FROM 
            `{$this->tableName}` AS deck
        LEFT JOIN 
            carte AS carte ON deck.id_deck = carte.id_deck
        GROUP BY 
            deck.id_deck
    ";

    $sth = $this->query($sql);
    return $sth->fetchAll();
}
public function findAllGroupedByStatus(): array
{
    $sql = "
        SELECT 
            deck.id_deck,
            deck.titre_deck,
            deck.date_debut,
            deck.date_fin_deck,
            deck.nb_cartes,
            COUNT(carte.id_carte) AS nb_cartes_atm,
            deck.nb_jaime,
            deck.id_administrateur,
            deck.status
        FROM 
            `{$this->tableName}` AS deck
        LEFT JOIN 
            carte AS carte ON deck.id_deck = carte.id_deck
        GROUP BY 
            deck.id_deck
        ORDER BY 
            FIELD(deck.status, 'Pending', 'WIP', 'Planned', 'Playable'),
            deck.date_debut ASC
    ";
    
    $sth = $this->query($sql);
    $decks = $sth->fetchAll();
    
    // Regrouper les decks par status
    $groupedDecks = [
        'Planned' => [],
        'WIP' => [],
        'Pending' => [],
        'Playable' => []
    ];
    
    foreach ($decks as $deck) {
        $groupedDecks[$deck['status']][] = $deck;
    }
    
    return $groupedDecks;
}
public function checkDeckDateOverlap(DateTime $date_debut, DateTime $date_fin_deck): bool
{
    $sql = "
        SELECT COUNT(*) as overlap_count
        FROM `{$this->tableName}` AS deck
        LEFT JOIN (
            SELECT id_deck, COUNT(id_carte) as nb_cartes_atm
            FROM carte
            GROUP BY id_deck
        ) AS carte_count ON deck.id_deck = carte_count.id_deck
        WHERE 
            (:date_debut <= date_fin_deck) 
            AND 
            (:date_fin_deck >= date_debut)
            AND (
                carte_count.nb_cartes_atm IS NULL 
                OR carte_count.nb_cartes_atm < deck.nb_cartes
            )
    ";

    try {
        $sth = $this->query(
            $sql,
            [
                ':date_debut' => $date_debut->format('Y-m-d'),
                ':date_fin_deck' => $date_fin_deck->format('Y-m-d')
            ]
        );
        
        $result = $sth->fetch();
        return (int)$result['overlap_count'] > 0;
        
    } catch (\Exception $e) {
        error_log("Erreur dans checkDeckDateOverlap: " . $e->getMessage());
        throw new \Exception("Une erreur est survenue lors de la vérification des dates du deck.");
    }
}



public function findDeckUpdatable(): array
{
    $sql = "
        SELECT 
            deck.id_deck,
            deck.titre_deck,
            deck.date_debut,
            deck.date_fin_deck,
            deck.nb_cartes,
            COUNT(carte.id_carte) AS nb_cartes_atm,
            deck.nb_jaime,
            deck.id_administrateur,
            deck.status
        FROM 
            `{$this->tableName}` AS deck
        LEFT JOIN 
            carte AS carte ON deck.id_deck = carte.id_deck
        WHERE 
            deck.status != 'Planned'
        GROUP BY 
            deck.id_deck
        ORDER BY 
            FIELD(deck.status, 'Pending', 'WIP', 'Playable'),
            deck.date_debut ASC
    ";
    
    $sth = $this->query($sql);
    $decks = $sth->fetchAll();
    
    // Regrouper les decks par status sans "Planned"
    $groupedDecks = [
        'WIP' => [],
        'Pending' => [],
        'Playable' => []
    ];
    
    foreach ($decks as $deck) {
        $groupedDecks[$deck['status']][] = $deck;
    }
    
    return $groupedDecks;
}
public function updateStatus(int $id_deck, string $status): bool
{
    $allowedStatuses = ['Pending', 'WIP', 'Planned', 'Playable'];
   
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception("Status invalide");
    }
   
    // Vérifier le statut actuel avant la mise à jour
    $sqlCheck = "
        SELECT status 
        FROM `{$this->tableName}` 
        WHERE id_deck = :id_deck
    ";
   
    $sthCheck = $this->query($sqlCheck, [
        ':id_deck' => $id_deck
    ]); 
    
    $currentStatus = $sthCheck->fetchColumn();
    
    // Si le statut est déjà celui demandé, retourner false
    if ($currentStatus === $status) {
        return false;
    }
   
    $sqlUpdate = "
        UPDATE `{$this->tableName}`
        SET status = :status
        WHERE id_deck = :id_deck
    ";
   
    $sthUpdate = $this->query($sqlUpdate, [
        ':status' => $status,
        ':id_deck' => $id_deck
    ]);
   
    return $sthUpdate->rowCount() > 0;
}

}

