<?php
/*
  Fichier : src/config/config.local.php
*/

/**
 * le DSN de la base
 */
define('APP_DB_SQLITE', 'mysql:host=localhost;dbname=db.ReignsWebapp;charset=UTF8');


define('APP_DB_USER', 'root');

/**
* le mot de passe de l'utilisateur MYSQL
*/
define('APP_DB_PASSWORD', '');

/**
* le préfixe des tables dans la base (utile pour les bases partagées)
*/
define('APP_TABLE_PREFIX', '');

