<?php
namespace StormBin\Package\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use Dotenv\Dotenv;

// Définir la fonction base_path() si elle n'existe pas
if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return dirname(__DIR__, 5) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

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
            echo "Avertissement : Fichier .env non trouvé. La connexion à la base de données pourrait ne pas fonctionner.\n";
        }

        // Récupérer les valeurs depuis les variables d'environnement
        $driver = $_ENV['DB_CONNECTION'] ?? null;

        // Si aucune connexion n'est définie, on ne fait rien
        if (!$driver) {
            echo "Aucune base de données configurée. Capsule ne sera pas initialisé.\n";
            return;
        }

        // Définir les valeurs communes
        $config = [
            'driver'    => $driver,
            'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8',
            'collation' => $_ENV['DB_COLLATION'] ?? 'utf8_unicode_ci',
            'prefix'    => $_ENV['DB_PREFIX'] ?? '',
        ];

        // Spécificité de SQLite
        if ($driver === 'sqlite') {
            $database = $_ENV['DB_DATABASE'] ?? null;
            if (!$database) {
                echo "Avertissement : Aucun fichier SQLite spécifié.\n";
                return;
            }
            $config['database'] = ($database === ':memory:') ? $database : base_path($database);
        } else {
            // Vérifier les paramètres essentiels pour les autres bases de données
            $host = $_ENV['DB_HOST'] ?? null;
            $database = $_ENV['DB_DATABASE'] ?? null;
            $username = $_ENV['DB_USERNAME'] ?? null;
            $password = $_ENV['DB_PASSWORD'] ?? null;

            if (!$host || !$database || !$username) {
                echo "Avertissement : Une ou plusieurs variables de connexion sont manquantes. Capsule ne sera pas initialisé.\n";
                return;
            }

            // Ajouter les configurations spécifiques
            $config += [
                'host'      => $host,
                'port'      => $_ENV['DB_PORT'] ?? null,
                'database'  => $database,
                'username'  => $username,
                'password'  => $password,
            ];
        }

        // Initialisation de Capsule
        $capsule = new Capsule;
        $capsule->addConnection($config);

        // Définition du conteneur Laravel
        $container = new Container();
        $capsule->setAsGlobal();
        $capsule->setEventDispatcher(new Dispatcher($container));
        $capsule->bootEloquent();

        // Associer l'application au container pour les facades
        Facade::setFacadeApplication($container);

        //echo "Base de données initialisée avec succès.\n";
    }
}
