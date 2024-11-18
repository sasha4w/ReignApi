<?php

namespace App\Helper;

class HTTP
{
    /**
     * Retourne l'URL complète.
     *
     * @param string $url
     * @return string
     * TODO vérifier le type de sortie string ?
     */
    public static function url(
        string $url = ''
    ): string {
        // ajouter le slash si nécéssaire
        $url = substr($url, 0, 1) != '/' ? '/' . $url : $url;
        echo APP_ROOT_URL_COMPLETE . $url;
    }

    /**
     * Redirige vers une route.
     *
     * @param string $url  la route vers laquelle la redirection doit s'opérer
     * @return void
     */
    public static function redirect(
        string $url = '/'
    ): void {
        header('Location: ' . APP_ROOT_URL_COMPLETE . $url);
    }
}