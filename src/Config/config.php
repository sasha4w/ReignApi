<?php
/*
  Fichier : src/Config/config.php

  Constantes de configuration générales.
  -----------------------------------------------
*/

// On vient charger les fichiers des constantes de configuration pour la base
// Si le projet ne nécéssite pas de base, alors ces fichiers peuvent rester vide.
// Deux fichiers peuvent être analysés selon la situation :
//   - config.local.php
//        pour le développement LOCAL
//   - config.prod.php
//        quand l'application fonctionne chez un hébergeur en production
//
// On détecte le local car l'IP de la machine est 127.0.0.1
$is_localhost = ($_SERVER['REMOTE_ADDR'] == '127.0.0.1' or $_SERVER['REMOTE_ADDR'] == '::1');
if ($is_localhost) {
    require 'config.local.php';
    define('APP_MODE', 'dev');
} else {
    require 'config.prod.php';
    define('APP_MODE', 'prod');
}

/** Séparateur entre dossiers qui correspond à \ sur un windows ou / sur un linux */
define('DS', DIRECTORY_SEPARATOR);

/** Chemin absolu vers le dossier du projet. */
define('APP_ROOT_DIRECTORY', realpath(__DIR__ . DS . '..' . DS . '..') . DS);

/** Chemin absolu vers le dossier de l'application */
define('APP_SRC_DIRECTORY', APP_ROOT_DIRECTORY . 'src' . DS);

/** Chemin absolu vers le dossier des ressources CSS,JS,IMAGES */
define('APP_ASSETS_DIRECTORY', APP_ROOT_DIRECTORY . 'public' . DS . 'assets' . DS);

/** URL partielle de l'application - cette constante est utile pour le router  */
define('APP_ROOT_URL', str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']));

/** chemin absolu vers le dossier de stockage */
define('APP_STORAGE_DIRECTORY', APP_SRC_DIRECTORY . 'Storage' . DS);

/** chemin absolu vers le dossier de stockage des logs */
define('APP_DEBUG_FILE_PATH', APP_STORAGE_DIRECTORY . 'logs');

/** URL complète de l'application en http:// ou https:// */
define(
    'APP_ROOT_URL_COMPLETE',
    isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] . "://{$_SERVER['HTTP_HOST']}" . APP_ROOT_URL :
  (strpos($_SERVER['SERVER_PROTOCOL'], 'HTTP/') !== false ? 'http' : 'https') .
  "://{$_SERVER['HTTP_HOST']}" . APP_ROOT_URL
);
