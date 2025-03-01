<?php

namespace StormBin\Package\Console;
use StormBin\Package\Commands\Controllers\MakeController;
use StormBin\Package\Commands\Models\MakeModel;

class Kernel 
{
    private $makeLogin, $makeMigration, $migrate, $makeController, $makeCrud, $makeModel;
    public function __construct(){
        $this->makeModel = new MakeModel();
        $this->makeController = new MakeController();
    }
    protected array $commands = [
        'serve' => 'serve',
        'make:controller'=>'makeController',
        'make:model'=>'makeModel',
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

public function makeModel($modelName){
    $this->makeModel->makeModel($modelName);
}


public function makeController($controllerName, $isApi = false)
{
    $this->makeController->makeController($controllerName, $isApi);
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
        echo "\32m Commandes disponibles :\n";
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
