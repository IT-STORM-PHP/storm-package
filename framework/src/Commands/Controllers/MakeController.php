<?php
    namespace StormBin\Package\Commands\Controllers;

    class MakeController{
        public function makeController($controllerName, $isApi = false){
            if (!$controllerName) {
                echo "❌ Veuillez fournir un nom pour le contrôleur.\n";
                exit(1);
            }
        
            // Mettre la première lettre en majuscule
            $controllerName = ucfirst($controllerName);
        
            // Déterminer le chemin absolu des fichiers modèles (stubs)
            $packagePath = dirname(__DIR__, 2); // Remonte de deux niveaux pour atteindre la racine du package
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
    }