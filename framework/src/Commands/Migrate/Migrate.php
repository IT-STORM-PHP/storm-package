<?php

namespace StormBin\Package\Commands\Migrate;

use Illuminate\Database\Capsule\Manager as Capsule;
use StormBin\Package\Database\Database;

class Migrate
{
    public function runMigrations()
    {
        Database::init();
        echo "Capsule initialisé.\n";

        $this->ensureMigrationsTableExists();
        echo "Table 'migrations' vérifiée.\n";

        $migrationsPath = "database/migrations/";
        $executedMigrations = $this->getExecutedMigrations();
        echo "Migrations déja exécutées : " . implode(', ', $executedMigrations) . "\n";

        foreach (glob($migrationsPath . '*.php') as $file) {
            $migrationName = basename($file, '.php');

            // Vérifie si la migration a déjà été exécutée
            if (in_array($migrationName, $executedMigrations)) {
                continue; // Ignore ce fichier et passe au suivant
            }

            echo "Application de la migration : {$migrationName}\n";

            // Inclure une seule fois le fichier pour éviter des erreurs
            $migration = require_once $file;

            try {
                // Vérifie si c'est bien une migration
                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up();
                    Capsule::table('migrations')->insert(['migration' => $migrationName]);
                    echo "✅ Migration appliquée avec succès : {$migrationName}\n";
                } else {
                    echo "⚠️ Erreur : {$migrationName} n'est pas une instance valide de Migration.\n";
                }
            } catch (\Exception $e) {
                echo "❌ Erreur lors de l'application de la migration {$migrationName} : " . $e->getMessage() . "\n";
            }
        }
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
            echo "Table 'migrations' créée avec succès.\n";
        }
    }
}
