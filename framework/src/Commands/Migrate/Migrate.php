<?php

namespace StormBin\Package\Commands\Migrate;

use Illuminate\Database\Capsule\Manager as Capsule;
use StormBin\Package\Database\Database;

class Migrate
{
    public function runMigrations()
    {
        Database::init();
        $this->ensureMigrationsTableExists();

        $migrationsPath = "database/migrations/";
        $executedMigrations = $this->getExecutedMigrations();
        $migrationFiles = glob($migrationsPath . '*.php');

        // Filtrer les migrations non exécutées
        $pendingMigrations = array_filter($migrationFiles, function ($file) use ($executedMigrations) {
            return !in_array(basename($file, '.php'), $executedMigrations);
        });

        if (empty($pendingMigrations)) {
            echo "\033[32mAucune nouvelle migration à exécuter. Tout est à jour !\033[0m\n";
            return;
        }

        echo "\n\033[34m🚀 Démarrage des migrations...\033[0m\n";
        foreach ($pendingMigrations as $file) {
            $migrationName = basename($file, '.php');
            echo "\033[33m➡️  Exécution de : $migrationName\033[0m\n";

            // Inclure et exécuter la migration
            $migration = require_once $file;

            try {
                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up();
                    Capsule::table('migrations')->insert(['migration' => $migrationName]);
                    echo "\033[32m✔ Migration appliquée : $migrationName\033[0m\n";
                } else {
                    echo "\033[31m⚠️ Erreur : $migrationName n'est pas une instance valide de Migration.\033[0m\n";
                }
            } catch (\Exception $e) {
                echo "\033[31m❌ Erreur lors de la migration $migrationName : " . $e->getMessage() . "\033[0m\n";
            }
        }

        echo "\n\033[32m✅ Toutes les migrations ont été exécutées avec succès !\033[0m\n";
    }

    private function getExecutedMigrations()
    {
        return Capsule::table('migrations')->pluck('migration')->toArray();
    }

    private function ensureMigrationsTableExists()
    {
        if (!Capsule::schema()->hasTable('migrations')) {
            Capsule::schema()->create('migrations', function ($table) {
                $table->id();
                $table->string('migration')->unique();
                $table->timestamp('created_at')->useCurrent();
            });
            echo "\033[32m📂 Table 'migrations' créée avec succès.\033[0m\n";
        }
    }
}
