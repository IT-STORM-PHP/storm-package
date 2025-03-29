<?php

namespace StormBin\Package\Console;

use StormBin\Package\Commands\Controllers\MakeController;
use StormBin\Package\Commands\Models\MakeModel;
use StormBin\Package\Commands\Migrations\MakeMigration;
use StormBin\Package\Commands\Migrate\Migrate;
use StormBin\Package\Commands\Crud\MakeCrud;
use StormBin\Package\Commands\Debug\DebugRoute;
class Kernel
{
    private $makeLogin, $makeMigration, $migrate, $makeController, $makeCrud, $makeModel, $debugRoute;

    public function __construct()
    {
        $this->makeModel = new MakeModel();
        $this->makeController = new MakeController();
        $this->makeMigration = new MakeMigration();
        $this->migrate = new Migrate();
        $this->makeCrud = new  MakeCrud();
        $this->debugRoute = new DebugRoute();
    }

    protected array $commands = [
        'serve' => 'serve',
        'make:controller' => 'makeController',
        'make:model' => 'makeModel',
        'make:migration' => 'makeMigration',
        'migrate' => 'migrate',
        'make:crud' => 'crud',
        'debug:route' => 'debugRoute',
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
        } elseif($command === 'make:crud') {
            $this->$method($argument, $isApi);
        } else {
            $this->$method($argument);
        }
    }

    public function debugRoute(){
            $this->debugRoute->handle();
    }

    //Appelle de la methode crud

    public function crud($tableName, $isApi = false)
{
    if (!$tableName) {
        echo "Veuillez fournir le nom de la table.\n";
        return;
    }

    echo "Génération du CRUD pour la table : $tableName\n";
    echo "Mode : " . ($isApi ? "API" : "Web") . "\n";

    $this->makeCrud->handle([$tableName, $isApi]);
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
    echo "Génération du contrôleur : $controllerName\n";
    echo "Mode : " . ($isApi ? "API" : "Web") . "\n";

    $this->makeController->makeController($controllerName, $isApi);
}

    // Méthode pour démarrer le serveur
    protected function serve()
    {
        global $argv;

        $host = "127.0.0.1";
        $port = 8000;

        foreach ($argv as $arg) {
            if (strpos($arg, '--host=') === 0) {
                $host = substr($arg, 7);
            } elseif (strpos($arg, '--port=') === 0) {
                $port = (int) substr($arg, 7);
            }
        }

        if (!shell_exec('php -v')) {
            $this->log("PHP n'est pas installé ou non accessible depuis le terminal.", "error");
            exit(1);
        }

        if (!is_dir("public")) {
            $this->log("Le dossier 'public' est introuvable. Vérifiez votre projet.", "error");
            exit(1);
        }

        $server = @stream_socket_server("tcp://$host:$port");
        if (!$server) {
            $this->log("⚠️  Impossible d'écouter sur $host:$port (Adresse déjà utilisée).", "error");
            $port++;
            $this->log("🔄 Recherche d'un port disponible...", "warning");

            while (!$server) {
                $server = @stream_socket_server("tcp://$host:$port");
                if (!$server) {
                    $port++;
                }
            }

            fclose($server);
        } else {
            fclose($server);
        }

        $this->log("✅ Serveur en cours d'exécution sur: \033[4;34mhttp://$host:$port\033[0m", "success");
        $this->log("🔵 Appuyez sur Ctrl + C pour arrêter le serveur", "info");

        // Lancer le serveur en arrière-plan et capturer les requêtes
        $logFile = "/tmp/storm_server.log";
        exec("php -S $host:$port -t public > $logFile 2>&1 &");

        while (true) {
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    // Mettre à jour l'expression régulière pour capturer le code de réponse, la méthode et l'URL
                    if (preg_match('/\[.*\] \d+\.\d+\.\d+\.\d+:\d+ \[(\d+)\]: (\w+) (\/[^\s]*)/', $line, $matches)) {
                        $statusCode = $matches[1]; // Code de réponse HTTP (200, 404, etc.)
                        $method = $matches[2];    // Méthode HTTP (GET, POST, etc.)
                        $url = $matches[3];       // L'URL
                        $time = rand(10, 500);   // Simuler le temps de réponse en ms

                        // Afficher le log avec le code de réponse, la méthode et l'URL
                        echo "\n$url:$method:$statusCode " . str_repeat(".", 45) . " ~ {$time}ms\n";
                    }
                }
                file_put_contents($logFile, ""); // Nettoyer le fichier
            }
            usleep(500000); // Attendre 0.5s pour éviter une surcharge CPU
        }
    }

    // Fonction pour afficher des logs colorés
    protected function log($message, $type = "info")
    {
        $colors = [
            "info" => "\033[34m",
            "success" => "\033[32m",
            "warning" => "\033[33m",
            "error" => "\033[31m"
        ];

        $reset = "\033[0m";
        $typeUpper = strtoupper($type);
        echo "{$colors[$type]}[$typeUpper] $message$reset\n";
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
        echo "\n  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration>\x1b[0m - Créer une migration avec le nom spécifié\n";
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -c\x1b[0m --tab=<nom_tab> Créer une table \x1b[32m\x1b[0m\n";
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -a\x1b[0m --tab=<nom_tab> Ajouter un attribut à la table \x1b[32m\x1b[0m\n";
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -r\x1b[0m --tab=<nom_tab> Retirer un attribut de la table \x1b[32m\x1b[0m\n";
        echo "  \x1b[32mmake:migration\x1b[0m \x1b[31m<nom_migration> -d\x1b[0m --tab=<nom_tab> Supprimer la table \x1b[32m\x1b[0m\n";

        // Option pour make:controller avec --api
        echo "\n  \x1b[32mmake:controller\x1b[0m \x1b[31m<nom_controller>\x1b[0m -- Créer un contrôleur\n";
        echo "  \x1b[32mmake:controller\x1b[0m \x1b[31m<nom_controller> --api\x1b[0m -- Créer un contrôleur dans le dossier \x1b[32mapi\x1b[0m\n";
        echo "  \x1b[32mmake:controller\x1b[0m \x1b[31m<nom_controller>\x1b[0m -- Créer un contrôleur dans le dossier \x1b[32mweb\x1b[0m\n";
    }
}
