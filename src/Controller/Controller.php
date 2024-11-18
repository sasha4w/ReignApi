<?php

namespace App\Controller;

class Controller
{
    protected $log;

    public function __construct()
    {
        // référence aux variables globales !!!
        global $logger;
        // ajouter 3 attributs pour tous les contrôleurs
        $this->log = $logger;
    }

    /**
     * Indiquer si la requête est de type AJAX.
     * Le header de la requête doit contenir le paramètre X-Requested-With=XMLHttpRequest
     *
     * @return boolean
     */
    public function isAjaxRequest(): bool
    {
        $headers = getallheaders();
        return isset($headers['X-Requested-With']) && $headers['X-Requested-With'] === 'XMLHttpRequest';
    }

    /**
     * Indiquer si la méthode est GET.
     *
     * @return boolean
     */
    public function isGetMethod(): bool
    {
        return 'GET' === strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Indiquer si la méthode est POST.
     *
     * @return boolean
     */
    public function isPostMethod(): bool
    {
        return 'POST' === strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * Retourner une structure JSON.
     *
     * @param array $response
     * @return string
     */
    public function json(array $response): string
    {
        header('Content-Type: application/json');
        return print(json_encode($response));
    }
}
