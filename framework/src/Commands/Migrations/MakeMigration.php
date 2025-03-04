<?php

namespace StormBin\Package\Commands\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use StormBin\Package\Database\Database;

class MakeMigration
{
    public function makeMigration($migrationName, $options = [])
    {
        if (!$migrationName) {
            die("Erreur : Veuillez spécifier un nom de migration.\n");
        }

        // Initialiser la base de données
        Database::init();

        // Vérifier si la table migrations existe, sinon la créer
        $this->ensureMigrationsTableExists();

        // Analyser les options et l'argument --tab
        $tableName = $options['tab'] ?? null;
        $action = $this->determineAction($options);

        // Générer le fichier de migration en fonction de l'action
        $this->generateMigrationFile($migrationName, $tableName, $action);
    }

    private function determineAction($options)
    {
        if (isset($options['c'])) {
            return 'create';
        } elseif (isset($options['a'])) {
            return 'add';
        } elseif (isset($options['r'])) {
            return 'remove';
        } elseif (isset($options['d'])) {
            return 'drop';
        } else {
            return 'default';
        }
    }

    private function ensureMigrationsTableExists()
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration');
                $table->timestamp('created_at')->useCurrent();
            });
            echo "Table 'migrations' créée avec succès.\n";
        }
    }

    private function generateMigrationFile($migrationName, $tableName, $action)
    {
        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$migrationName}.php";
        $filePath = "database/migrations/{$fileName}";

        // Choisir le bon stub en fonction de l'action
        $stubFile = $this->getStubFile($action);
        $stub = file_get_contents($stubFile);

        // Remplacer les placeholders dans le stub
        $stub = str_replace('{{tableName}}', $tableName ?? 'default_table', $stub);
        $stub = str_replace('{{migrationName}}', $migrationName, $stub);

        // Sauvegarder le fichier
        file_put_contents($filePath, $stub);
        echo "Migration créée : {$filePath}\n";
    }


    private function getStubFile($action)
    {
        $stubDir = __DIR__ . '/../../StubFiles/Migrations/';

        switch ($action) {
            case 'create':
                return $stubDir . 'create_table.stub';
            case 'add':
                return $stubDir . 'add_column.stub';
            case 'remove':
                return $stubDir . 'remove_column.stub';
            case 'drop':
                return $stubDir . 'drop_table.stub';
            default:
                return $stubDir . 'default_migration.stub';
        }
    }
}
