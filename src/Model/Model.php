<?php
/*
  Fichier : src/Model/Model.php
*/
declare(strict_types=1);

namespace App\Model;

use PDOStatement;

/**
 * Classe CRUD modèle qui contient les 7 méthodes :
 *   - findAll()                        pour rechercher toutes les données
 *   - find( int $id )                  pour rechercher un identifiant
 *   - findAllBy( array $criterias )    pour rechercher des données en fonction d'un/ou plusieurs critères
 *   - findOneBy( array $criterias )    pour rechercher une donnée en fonction d'un/ou plusieurs critères
 *   - create( array $datas )           pour ajouter une donnée
 *   - update( int $id, array $datas )  pour mettre à jour une donnée
 *   - delete( int $id )                pour effacer une donnée
 *   - exist( int $id )                 pour vérifier si une donnée existe
 */
class Model
{
    protected $tableName;
    // instance de la classe
    protected static $dbh;

    public function __construct()
    {
        if (!self::$dbh) {
            try {
                self::$dbh = new \PDO(
                    APP_DB_DSN,
                    // nom de l'utilisateur MYSQL
                    APP_DB_USER,
                    // mot de passe de l'utilisateur MYSQL
                    APP_DB_PASSWORD,
                    // réglage d'options qui permet de récupérer les informations de la base
                    // sous forme de tableau associatif
                    // et de demander de déclencher une exception quand une erreur de SQL est détectée
                    [
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ]
                );
            } catch (\Exception $e) {
                // ICI on vient écrire le message qui doit s'afficher quand
                // une erreur de connexion à la base est produite
                // ou quand une erreur de syntaxe SQL est rencontrée

                // affichage d'un message résumé en couleur
                echo '<div style="font-size: 22px;color: red;padding: 2rem">';
                echo "<h1>ERREUR</h1><p>{$e->getMessage()}</p>";
                // si on ne veut pas donner trop de détail à l'internaute, alors on peut écrire
                // echo "<h1>ERREUR</h1></p>";
                echo '</div>';
                // arrêt du script
                die();
            }
        }
    }

     /**
     * Retourne les informations d'un identifiant.
     *
     * @param  integer  $id identifiant de la donnée
     * @return array|null
     */
    public function find(
        int $id
    ): ?array {
        $sql = "SELECT * FROM `{$this->tableName}` WHERE id = :id";
        $sth = $this->query($sql, [':id' => $id]);
        $rows = $sth->fetch();
        if ($rows && count($rows)) {
            return $rows;
        }
        return null;
    }

    /**
     * Retourne toutes les informations de la table.
     *
     * @return array
     */
    public function findAll(): ?array
    {
        $sql = "SELECT * FROM `{$this->tableName}`";
        $sth = $this->query($sql);
        if ($sth) {
            return $sth->fetchAll();
        }
        return [];
    }

    /**
     * Retourne les informations associées à un/des critères.
     *
     * @param  array  $criterias le tableau des critères
     * @return array|null
     */
    public function findAllBy(
        array $criterias = []
    ): ?array {
        // décomposer le tableau des critères
        foreach ($criterias as $f => $v) {
            $fields[] = "$f = ?";
            $values[] = $v;
        }
        // On transforme le tableau en chaîne de caractères séparée par des AND
        $fields_list = implode(' AND ', $fields);
        $sql = "SELECT * FROM `{$this->tableName}` WHERE $fields_list";
        return $this->query($sql, $values)->fetchAll();
    }

    /**
     * Retourne les informations d'un tuplet associées à un/des critères.
     *
     * @param array $criterias
     * @return array|null
     */
    public function findOneBy(
        array $criterias = []
    ): ?array {
        $result = $this->findAllBy($criterias);
        return $result ? $result[0] : null;
    }

    /**
     * Indique si l'identifiant existe déjà dans la base.
     *
     * @param  integer  $id identifiant à tester.
     * @return bool
     */
    public function exists(
        int $id
    ): bool {
        $sql = "SELECT COUNT(*) AS c FROM `{$this->tableName}` WHERE id = :id";
        $sth = $this->query($sql, [':id' => $id]);
        if ($sth) {
            return ($sth->fetch()['c'] > 0);
        }
        return false;
    }

    /**
     * Ajoute les nouvelles informations dans une table.
     *
     * @param  array  $datas  données à ajouter organisées sous forme de tableau associatif.
     * @return integer|null
     */
    public function create(
        array $datas
    ): ?int {
        $sql = 'INSERT INTO `' . $this->tableName . '` ( ';
        foreach (array_keys($datas) as $k) {
            $sql .= " {$k} ,";
        }
        $sql = substr($sql, 0, strlen($sql) - 1) . ' ) VALUES (';
        foreach (array_keys($datas) as $k) {
            $sql .= " :{$k} ,";
        }
        $sql = substr($sql, 0, strlen($sql) - 1) . ' )';
        foreach (array_keys($datas) as $k) {
            $attributes[':' . $k] = $datas[$k];
        }
        $sth = $this->query($sql, $attributes);
        if ($sth) {
            return (int)self::$dbh->lastInsertId();
        }

        return null;
    }

    /**
     * Édite les  informations d'un identifiant.
     *
     * @param  integer  $id     identifiant à modifier.
     * @param  array  $datas    tableau associatif des données à modifier.
     * @return bool
     */
    public function update(
        int $id,
        array $datas
    ): bool {
        $sql = 'UPDATE `' . $this->tableName . '` SET ';
        foreach (array_keys($datas) as $k) {
            $sql .= " {$k} = :{$k} ,";
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        $sql .= ' WHERE id =:id';
        foreach (array_keys($datas) as $k) {
            $attributes[':' . $k] = $datas[$k];
        }
        $attributes[':id'] = $id;
        $sth = $this->query($sql, $attributes);

        return $sth->rowCount() > 0;
    }

    /**
     * Efface l'identifiant.
     *
     * @param  integer  $id identifiant à effacer
     * @return bool
     */
    public function delete(
        int $id
    ): bool {
        $sql = "DELETE FROM `{$this->tableName}` WHERE id = :id";
        $sth = $this->query($sql, [':id' => $id]);
        return $sth->rowCount() > 0;
    }

    /**
     * Excécute une requête.
     *
     * @param string $sql           expression SQL à traiter
     * @param array|null $attributs      tableau des attributs
     * @return boolean|PDOStatement
     */
    public function query(
        string $sql,
        array $attributs = null
    ): bool|PDOStatement {
        // si des attributs sont spécifiés ...
        if ($attributs !== null) {
            // Requête préparée
            $sth = self::$dbh->prepare($sql);
            $sth->execute($attributs);
            return $sth;
        } else {
            // ... sinon faire une requête simple
            return self::$dbh->query($sql);
        }
    }
}
