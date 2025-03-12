<?php

namespace StormBin\Package\Commands\Crud;

use StormBin\Package\Database\Database;
use StormBin\Package\Commands\Crud\Services\ModelGenerator;
use StormBin\Package\Commands\Crud\Services\ForeignKeyHandler;
use StormBin\Package\Commands\Crud\Services\ControllerGenerator;
class MakeCrud
{
    protected $modelGenerator;
    protected $foreignKeyHandler;
    protected $controllerGenerator;

    public function __construct()
    {
        $this->modelGenerator = new ModelGenerator();
        $this->foreignKeyHandler = new ForeignKeyHandler();
        
    }

    public function handle($args)
    {
        // Initialiser la base de données
        Database::init();

        if (empty($args)) {
            echo "Veuillez fournir le nom de la table.\n";
            return;
        }

        // Supprimer les espaces supplémentaires et forcer la casse en minuscule
        $tableName = trim(strtolower($args[0]));

        // Vérifier si la table existe
        if (!$this->modelGenerator->tableExists($tableName)) {
            echo "La table '$tableName' n'existe pas.\n";
            return;
        }

        // Créer le modèle à partir de la structure de la table
        $this->createModel($tableName);
        // Générer le contrôleur CRUD pour le modèle
        $this->createController($tableName);

    }

    private function createModel($tableName)
    {
        // Récupérer les clés étrangères
        $foreignKeys = $this->foreignKeyHandler->getForeignKeys($tableName);

        // Créer les modèles des tables référencées si elles n'existent pas
        foreach ($foreignKeys as $foreignKey) {
            if (isset($foreignKey->foreignModel) && !$this->modelGenerator->tableHasModel($foreignKey->foreignModel)) {
                echo "Création du modèle pour la table référencée : {$foreignKey->foreignModel}\n";
                $this->createModel($foreignKey->foreignModel);
            }
        }

        // Générer le modèle pour la table actuelle
        $this->modelGenerator->generateModel($tableName, $foreignKeys);
    }
    private function createController($tableName)
{
    $modelName = ucfirst($tableName); // Convertir le nom de la table en nom de modèle
    $foreignKeys = $this->foreignKeyHandler->getForeignKeys($tableName); // Récupérer les clés étrangères
    $controllerGenerator = new ControllerGenerator($modelName, $foreignKeys);
    $controllerGenerator->generateController();
}
}