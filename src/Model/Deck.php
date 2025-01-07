<?php

declare(strict_types=1);

namespace App\Model;

class Deck extends Model
{
    use TraitInstance;

    protected $tableName = APP_TABLE_PREFIX . 'deck';
    public function findPlayableDecks(): array
{
    $sql = "
        SELECT * 
        FROM `{$this->tableName}`
        WHERE 
            nb_cartes = (
                SELECT COUNT(*) 
                FROM carte 
                WHERE carte.id_deck = {$this->tableName}.id_deck
            )
            OR NOW() > date_fin_deck
    ";

    $sth = $this->query($sql);
    return $sth->fetchAll();
}
public function findDeckUpdatable(): array
{
    // récupérer les decks modifiables et celui avec la date de début la plus ancienne
    $sql = "
        SELECT * 
        FROM `{$this->tableName}`
        WHERE 
            nb_cartes < (
                SELECT COUNT(*) 
                FROM carte 
                WHERE carte.id_deck = {$this->tableName}.id_deck
            )
            AND date_fin_deck > NOW()
        ORDER BY date_debut ASC
        LIMIT 1
    ";

    $sth = $this->query($sql);
    return $sth->fetchAll();
}


}

