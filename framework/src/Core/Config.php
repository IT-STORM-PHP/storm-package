<?php

namespace StormBin\Package\Core;
class Config
{
    private static array $config = [];

    /**
     * Charge les variables du fichier .env
     */
    public static function loadEnv(string $path)
    {
        if (!file_exists($path)) {
            throw new \Exception(".env file not found at: " . $path);
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue; // Ignorer les commentaires
            }
            list($key, $value) = explode('=', $line, 2);
            self::$config[trim($key)] = trim($value);
        }
    }

    /**
     * Récupère une valeur de configuration
     */
    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }
}
