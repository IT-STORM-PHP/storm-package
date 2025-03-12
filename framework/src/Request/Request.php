<?php

namespace StormBin\Package\Request;

use Exception;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Factory as ValidatorFactory;

class Request
{
    private array $data;
    private array $files;
    private array $headers;
    private array $cookies;
    private array $server;

    public function __construct()
    {
        // Fusionner les entrées POST et GET
        $this->data = array_merge($_GET, $_POST);

        // Récupérer les fichiers téléchargés
        $this->files = $_FILES;

        // Récupérer les en-têtes
        $this->headers = $this->getAllHeaders();

        // Récupérer les cookies
        $this->cookies = $_COOKIE;

        // Récupérer les informations du serveur
        $this->server = $_SERVER;
    }

    /**
     * Vérifie si une clé existe dans les données.
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Récupère la valeur d'un champ avec une valeur par défaut.
     */
    public function get(string $key, $default = null)
    {
        return $this->has($key) ? htmlspecialchars($this->data[$key], ENT_QUOTES, 'UTF-8') : $default;
    }

    /**
     * Récupère toutes les données nettoyées.
     */
    public function all(): array
    {
        return array_map(fn($item) => htmlspecialchars($item, ENT_QUOTES, 'UTF-8'), $this->data);
    }

    /**
     * Récupère un fichier téléchargé.
     */
    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Vérifie si un fichier a été téléchargé.
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]);
    }

    /**
     * Récupère un en-tête de la requête.
     */
    public function header(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Récupère un cookie.
     */
    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Récupère la méthode HTTP de la requête.
     */
    public function method(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Vérifie si la requête est de type GET.
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * Vérifie si la requête est de type POST.
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Vérifie si la requête est de type PUT.
     */
    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    /**
     * Vérifie si la requête est de type DELETE.
     */
    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    /**
     * Valider les données de la requête.
     *
     * @param array $rules Les règles de validation
     * @param array $messages Les messages d'erreur personnalisés
     * @return array Les données validées
     * @throws ValidationException Si la validation échoue
     */
    public function validate(array $rules, array $messages = [])
    {
        $validator = $this->getValidatorFactory()->make($this->data, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Récupère une instance de ValidatorFactory.
     */
    /**
 * Récupère une instance de ValidatorFactory.
 */
private function getValidatorFactory(): ValidatorFactory
{
    // Créer un traducteur (Translator)
    $translator = new \Illuminate\Translation\Translator(
        new \Illuminate\Translation\ArrayLoader, // Chargeur de traductions
        'en' // Langue par défaut
    );

    // Créer une instance de ValidatorFactory
    return new ValidatorFactory($translator, null); // Pas de conteneur d'injection de dépendances
}

    /**
     * Récupère tous les en-têtes de la requête.
     */
    private function getAllHeaders(): array
{
    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (strpos($name, 'HTTP_') === 0) {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
    return $headers;
}
}