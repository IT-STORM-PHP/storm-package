<?php

namespace StormBin\Package\Request;

class Request
{
    private array $data;

    public function __construct()
    {
        // Fusionner les entrées POST et GET
        $this->data = array_merge($_GET, $_POST);
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
}