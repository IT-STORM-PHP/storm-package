<?php

namespace StormBin\Package\Console;


class Kernel 
{
    
    protected array $commands = [
        'serve' => 'serve',
        'make:controller'=>'makeController'

    ];

    public function handle($argv)
{
    $command = $argv[1] ?? null;
    $argument = $argv[2] ?? null;
    $isApi = in_array('--api', $argv); // Vérifie si l'option --api est passée

    if (!$command || !isset($this->commands[$command])) {
        $this->showUsage();
        exit(1);
    }

    $method = $this->commands[$command];

    // Passer l'argument et l'option --api si nécessaire
    if ($command === 'make:controller') {
        $this->$method($argument, $isApi);
    } else {
        $this->$method($argument);
    }
}


public function makeController($controllerName, $isApi = false)
{
    if (!$controllerName) {
        echo "❌ Veuillez fournir un nom pour le contrôleur.\n";
        exit(1);
    }

    // Mettre la première lettre en majuscule
    $controllerName = ucfirst($controllerName);

    // Déterminer le chemin absolu des fichiers modèles (stubs)
    $packagePath = dirname(__DIR__, 1); // Remonte de deux niveaux pour atteindre la racine du package
    if ($isApi) {
        $stubPath = "$packagePath/StubFiles/Controllers/api/controller.stub";
        $filePath = getcwd() . "/app/api/Controllers/{$controllerName}.php";
    } else {
        $stubPath = "$packagePath/StubFiles/Controllers/web/controller.stub";
        $filePath = getcwd() . "/app/web/Controllers/{$controllerName}.php";
    }

    // Vérifier si le contrôleur existe déjà
    if (file_exists($filePath)) {
        echo "❌ Le contrôleur '$controllerName' existe déjà.\n";
        exit(1);
    }

    // Vérifier l'existence du fichier stub
    if (!file_exists($stubPath)) {
        echo "❌ Le fichier modèle '$stubPath' est introuvable.\n";
        exit(1);
    }

    // Lire et remplacer les placeholders du fichier stub
    $content = file_get_contents($stubPath);
    $content = str_replace('{{controllerName}}', $controllerName, $content);

    // Créer le dossier s'il n'existe pas
    if (!file_exists(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }

    // Créer le fichier du contrôleur
    file_put_contents($filePath, $content);

    $location = $isApi ? 'app/api/Controllers' : 'app/web/Controllers';
    echo "✅ Contrôleur '$controllerName' créé dans '$location'.\n";
}



    
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

    



    protected function showUsage()
    {
        echo "Usage: php storm <commande>\n";
        echo "Commandes disponibles :\n";
        echo "  serve             Démarrer le serveur local\n";
        echo "  make:migration   Créer un fichier de migration\n";
        echo "  migrate           Exécuter les migrations\n";
        //echo "  migrateTest       Exécuter les migrations avec possibilité de mise à jour des tables (test) \n";
        echo "  rollback          Annuler la dernière migration\n";
        echo '  make:crud         Créer un modèle et un contrôleur CRUD pour une table existante' . "\n";
        echo '  make:controller  Créer un contrôleur' . "\n";
        echo '  make:login        Créer un système de connexion avec une table existante' . "\n";
        echo '  make:model        Créer un modèle' . "\n";
    }
    
}
