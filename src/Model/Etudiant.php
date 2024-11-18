<?php

declare(strict_types=1);

namespace App\Model;

class Etudiant extends Model
{
protected $tableName = APP_TABLE_PREFIX . 'etudiant';
protected $tableParcours = APP_TABLE_PREFIX . 'parcours';
protected static $instance;
public static function getInstance(): Etudiant { 
    if(self::$instance === null){
        self::$instance = new Etudiant();
    }
    return self::$instance;
 }
public function findAllDetailled(): array {
$sql = "SELECT e.*,p.nom as parcours
FROM {$this->tableName} e, {$this->tableParcours} p WHERE e.parcours_id = p.id ORDER BY e.nom,e.prenom ";
$sth = self::$dbh->prepare(query: $sql);
$sth->execute();
return $sth->fetchAll();
}
}
