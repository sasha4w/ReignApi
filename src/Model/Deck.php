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

}

