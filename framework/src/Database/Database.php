<?php

namespace StormBin\Package\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Dotenv\Dotenv;

class Database
{
    public static function init()
    {
        // Définir le chemin vers la racine
        $rootPath = dirname(__DIR__, 5);
        $envFilePath = $rootPath . '/.env';

        // Charger le fichier .env si disponible
        if (file_exists($envFilePath)) {
            $dotenv = Dotenv::createImmutable($rootPath);
            $dotenv->load();
        } else {
            exit; // Arrêter l'exécution si le fichier .env est absent
        }

        // Récupérer les valeurs depuis les variables d'environnement
        $driver = $_ENV['DB_CONNECTION'] ?? null;
        $host = $_ENV['DB_HOST'] ?? null;
        $port = $_ENV['DB_PORT'] ?? null;
        $database = $_ENV['DB_DATABASE'] ?? null;
        $username = $_ENV['DB_USERNAME'] ?? null;
        $password = $_ENV['DB_PASSWORD'] ?? null;
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8';
        $collation = $_ENV['DB_COLLATION'] ?? 'utf8_unicode_ci';
        $prefix = $_ENV['DB_PREFIX'] ?? '';

        if (!$driver || !$host || !$database || !$username) {
            exit; // Arrêter l'exécution si une variable essentielle est manquante
        }

        // Configuration de la connexion à la base de données
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => $driver,
            'host'      => $host,
            'port'      => $port,
            'database'  => $database,
            'username'  => $username,
            'password'  => $password,
            'charset'   => $charset,
            'collation' => $collation,
            'prefix'    => $prefix,
        ]);

        try {
            $capsule->getConnection()->getPdo();
        } catch (\Exception $e) {
            exit; // Arrêter l'exécution en cas d'échec de connexion
        }

        // Démarrer Eloquent
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
