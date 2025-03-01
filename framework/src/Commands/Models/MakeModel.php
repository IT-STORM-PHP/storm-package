<?php

    namespace StormBin\Package\Commands\Models;
    
    class MakeModel{
        public function makeModel($modelName){
            if (!$modelName) {
                echo "❌ Veuillez fournir un nom pour le model.\n";
                exit(1);
            }
            $modelName = ucfirst($modelName);
            $packagePath = dirname(__DIR__, 2);
        
            $stubPath = "$packagePath/StubFiles/Models/Model.stub";
            $filePath = getcwd() . "/app/web/Models/{$modelName}.php";
        
        
            // Vérifier si le contrôleur existe déjà
            if (file_exists($filePath)) {
                echo "❌ Le model '$modelName' existe déjà.\n";
                exit(1);
            }
        
            // Vérifier l'existence du fichier stub
            if (!file_exists($stubPath)) {
                echo "❌ Le fichier modèle '$stubPath' est introuvable.\n";
                exit(1);
            }
        
            // Lire et remplacer les placeholders du fichier stub
            $content = file_get_contents($stubPath);
            $content = str_replace('{{model}}', $modelName, $content);
        
            // Créer le dossier s'il n'existe pas
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0777, true);
            }
        
            // Créer le fichier du modele
            file_put_contents($filePath, $content);
        
            $location =  'app/web/Models';
            echo "✅ Modèle '$modelName' créé dans '$location'.\n";
        }
    }