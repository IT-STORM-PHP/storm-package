<?php

namespace StormBin\Package\Console;

use StormBin\Package\Commands\Controllers\MakeController;
use StormBin\Package\Commands\Models\MakeModel;
use StormBin\Package\Commands\Migrations\MakeMigration;
use StormBin\Package\Commands\Migrate\Migrate;

class Kernel 
{
    private $makeLogin, $makeMigration, $migrate, $makeController, $makeCrud, $makeModel;

    public function __construct()
    {
        $this->makeModel = new MakeModel();
        $this->makeController = new MakeController();
        $this->makeMigration = new MakeMigration();
        $this->migrate = new Migrate();
    }

    protected array $commands = [
        'serve' => 'serve',
        'make:controller' => 'makeController',
        'make:model' => 'makeModel',
        'make:migration' => 'makeMigration',
        'migrate' => 'migrate',
    ];

    public function handle($argv)
    {
        $command = $argv[1] ?? null;
        $argument = $argv[2] ?? null;
        $isApi = in_array('--api', $argv); // Vérifie si l'option --api est passée

        // Récupération des options pour la migration et autres commandes
        $options = [];
        foreach ($argv as $arg) {
            if (strpos($arg, '--tab=') === 0) {
                $options['tab'] = substr($arg, 6);
            } elseif ($arg === '-c') {
                $options['c'] = true;
            } elseif ($arg === '-a') {
                $options['a'] = true;
            } elseif ($arg === '-r') {
                $options['r'] = true;
            } elseif ($arg === '-d') {
                $options['d'] = true;
            }
        }

        if (!$command || !isset($this->commands[$command])) {
            $this->showUsage();
            exit(1);
        }

        $method = $this->commands[$command];

        // Exécution des commandes avec options si applicable
        if ($command === 'make:migration') {
            $this->$method($argument, $options);
        } elseif ($command === 'make:controller') {
            $this->$method($argument, $isApi);
        } else {
            $this->$method($argument);
        }
    }

    // Méthode pour créer une migration
    public function makeMigration($migrationName, $options)
    {
        $this->makeMigration->makeMigration($migrationName, $options);
    }

    // Méthode pour exécuter les migrations
    public function migrate()
    {
        $this->migrate->runMigrations();
    }

    // Méthode pour créer un modèle
    public function makeModel($modelName)
    {
        $this->makeModel->makeModel($modelName);
    }

    // Méthode pour créer un contrôleur
    public function makeController($controllerName, $isApi = false)
    {
        $this->makeController->makeController($controllerName, $isApi);
    }

    // Méthode pour démarrer le serveur
    protected function serve()
    {
        global $argv;

        $host = "127.0.0.1"; // Valeur par défaut
        $port = 8000;        // Valeur par défaut

        foreach ($argv as $arg) {
            if (strpos($arg, '--host=') === 0) {
                $host = substr($arg, 7);
            } elseif (strpos($arg, '--port=') === 0) {
                $port = (int) substr($arg, 7);
            }
        }

        while (!@stream_socket_server("tcp://$host:$port")) {
            $port++; // Incrémente si le port est occupé
        }

        $cmd = "php -S $host:$port -t public";
        echo "Serveur démarré sur http://$host:$port\n";
        exec($cmd);
    }

    // Affichage des commandes disponibles et de leur usage
    protected function showUsage()
    {
        echo "Usage: php storm <commande>\n";
        echo "\x1b[32mCommandes disponibles :\x1b[0m\n"; // Vert pour les commandes

        echo "  \x1b[32mmake:migration\x1b[0m   Créer un fichier de migration\n";
        echo "  \x1b[32mmigrate\x1b[0m           Exécuter les migrations\n";
        echo "  \x1b[32mrollback\x1b[0m          Annuler la dernière migration\n";
        echo "  \x1b[32mmake:crud\x1b[0m         Créer un modèle et un contrôleur CRUD pour une table existante\n";
        echo "  \x1b[32mmake:controller\x1b[0m  Créer un contrôleur\n";
        echo "  \x1b[32mmake:login\x1b[0m        Créer un système de connexion avec une table existante\n";
        echo "  \x1b[32mmake:model\x1b[0m        Créer un modèle\n";

        // Commandes avec options
        echo "\n  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration>\x1b[0m - Créer une migration avec le nom spécifié\n"; // 'make:migration <nom_migration>' en vert
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -c\x1b[0m -- Créer une table \x1b[32m<nom_tab>\x1b[0m\n"; // --tab=<nom_tab> intégré
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -a\x1b[0m -- Ajouter un attribut à la table \x1b[32m<nom_tab>\x1b[0m\n";
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -r\x1b[0m -- Retirer un attribut de la table \x1b[32m<nom_tab>\x1b[0m\n";
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -d\x1b[0m -- Supprimer la table \x1b[32m<nom_tab>\x1b[0m\n";

        // Option pour make:controller avec --api
        echo "\n  \x1b[32mmake:controller\x1b[0m \x1b[31m<nom_controller>\x1b[0m -- Créer un contrôleur\n";
        echo "  \x1b[32mmake:controller\x1b[0m \x1b[31m<nom_controller> --api\x1b[0m -- Créer un contrôleur dans le dossier \x1b[32mapi\x1b[0m\n";
        echo "  \x1b[32mmake:controller\x1b[0m \x1b[31m<nom_controller>\x1b[0m -- Créer un contrôleur dans le dossier \x1b[32mweb\x1b[0m\n";
    }
}
