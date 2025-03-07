<?php

namespace StormBin\Package\Commands\Crud;

use StormBin\Package\Database\Database;
use Illuminate\Database\Capsule\Manager as Capsule;
use Exception;

class MakeCrud
{
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
        if (!$this->tableExists($tableName)) {
            echo "La table '$tableName' n'existe pas.\n";
            return;
        }

        // Créer le modèle à partir de la structure de la table
        $this->createModel($tableName);
    }

    private function tableExists($tableName)
    {
        return Capsule::schema()->hasTable($tableName);
    }

    private function createModel($tableName)
    {
        // Récupérer les colonnes de la table
        $columns = Capsule::schema()->getColumnListing($tableName);

        // Récupérer les détails des colonnes
        $columnDetails = [];
        foreach ($columns as $column) {
            $columnDetails[$column] = $this->getColumnDetails($tableName, $column);
        }

        // Déterminer la clé primaire
        $primaryKey = $this->getPrimaryKey($tableName);

        // Déterminer si les timestamps existent
        $timestamps = $this->hasTimestamps($columns);

        // Récupérer les clés étrangères
        $foreignKeys = $this->getForeignKeys($tableName);

        // Générer le contenu du modèle à partir du modèle "stub"
        $modelContent = $this->generateModelContent($tableName, $primaryKey, $columns, $timestamps, $foreignKeys);

        // Définir le chemin du modèle
        $modelPath = getcwd() . "/app/Models/" . ucfirst($tableName) . ".php";

        // Créer le dossier Models s'il n'existe pas
        if (!is_dir(dirname($modelPath))) {
            mkdir(dirname($modelPath), 0755, true);
        }

        // Sauvegarder le modèle
        file_put_contents($modelPath, $modelContent);

        echo "Modèle créé : $modelPath\n";
    }

    private function getColumnDetails($tableName, $column)
    {
        $connection = Capsule::connection();
        $databaseType = $connection->getDriverName();

        try {
            if ($databaseType === 'sqlite') {
                // Pour SQLite : utiliser PRAGMA table_info
                $query = "PRAGMA table_info($tableName)";
                $result = $connection->select($query);

                foreach ($result as $row) {
                    if ($row->name === $column) {
                        return [
                            'type' => $row->type,
                            'nullable' => !$row->notnull,
                        ];
                    }
                }
            } elseif ($databaseType === 'mysql') {
                // Pour MySQL : utiliser DESCRIBE
                $query = "DESCRIBE $tableName";
                $result = $connection->select($query);

                foreach ($result as $row) {
                    if ($row->Field === $column) {
                        return [
                            'type' => $row->Type,
                            'nullable' => $row->Null === 'YES',
                        ];
                    }
                }
            } elseif ($databaseType === 'pgsql') {
                // Pour PostgreSQL : utiliser information_schema.columns
                $query = "SELECT data_type, is_nullable FROM information_schema.columns WHERE table_name = ? AND column_name = ?";
                $result = $connection->select($query, [$tableName, $column]);

                if (!empty($result)) {
                    return [
                        'type' => $result[0]->data_type,
                        'nullable' => $result[0]->is_nullable === 'YES',
                    ];
                }
            }
        } catch (Exception $e) {
            return [
                'type' => 'unknown',
                'nullable' => false,
            ];
        }

        return [
            'type' => 'unknown',
            'nullable' => false,
        ];
    }

    private function getPrimaryKey($tableName)
    {
        $connection = Capsule::connection();
        $databaseType = $connection->getDriverName();

        if ($databaseType === 'sqlite') {
            // Pour SQLite : utiliser PRAGMA table_info
            $query = "PRAGMA table_info($tableName)";
            $result = $connection->select($query);

            foreach ($result as $row) {
                if ($row->pk) {
                    return $row->name;
                }
            }
        } elseif ($databaseType === 'mysql') {
            // Pour MySQL : utiliser SHOW KEYS
            $query = "SHOW KEYS FROM $tableName WHERE Key_name = 'PRIMARY'";
            $result = $connection->select($query);

            if (!empty($result)) {
                return $result[0]->Column_name;
            }
        } elseif ($databaseType === 'pgsql') {
            // Pour PostgreSQL : utiliser information_schema.table_constraints
            $query = "SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ? AND constraint_name = ?";
            $result = $connection->select($query, [$tableName, 'PRIMARY']);

            if (!empty($result)) {
                return $result[0]->column_name;
            }
        }

        return 'id'; // Par défaut, on suppose que la clé primaire est 'id'
    }

    private function hasTimestamps($columns)
    {
        return in_array('created_at', $columns) && in_array('updated_at', $columns);
    }

    private function getForeignKeys($tableName)
    {
        $connection = Capsule::connection();
        $databaseType = $connection->getDriverName();

        if ($databaseType === 'mysql') {
            // Pour MySQL : utiliser INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            $query = "
                SELECT 
                    COLUMN_NAME AS foreignKey,
                    REFERENCED_TABLE_NAME AS foreignModel
                FROM 
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE 
                    TABLE_NAME = ? 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
            ";
            return $connection->select($query, [$tableName]);
        } elseif ($databaseType === 'pgsql') {
            // Pour PostgreSQL : utiliser information_schema.key_column_usage
            $query = "
                SELECT 
                    column_name AS foreignKey,
                    referenced_table_name AS foreignModel
                FROM 
                    information_schema.key_column_usage
                WHERE 
                    table_name = ? 
                    AND referenced_table_name IS NOT NULL
            ";
            return $connection->select($query, [$tableName]);
        }

        return []; // SQLite ne supporte pas les clés étrangères via des requêtes SQL
    }

    private function generateModelContent($tableName, $primaryKey, $columns, $timestamps, $foreignKeys)
{
    $fillable = array_filter($columns, function($column) use ($primaryKey) {
        return $column !== 'created_at' && $column !== 'updated_at' && $column !== $primaryKey;
    });

    $modelTemplate = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class " . ucfirst($tableName) . " extends Model
{
    use HasFactory;

    // Définir le nom de la table
    protected \$table = '$tableName'; // Nom de la table

    // Définir la clé primaire
    protected \$primaryKey = '$primaryKey'; // Clé primaire de la table

    // Champs remplissables
    protected \$fillable = [";

    // Ajoutez dynamiquement les champs remplissables
    foreach ($fillable as $field) {
        $modelTemplate .= "'$field', ";
    }

    $modelTemplate = rtrim($modelTemplate, ', ') . "];

    // Champs protégés
    protected \$guarded = [];

    // Pour les timestamps
    public \$timestamps = " . ($timestamps ? 'true' : 'false') . "; // Active created_at et updated_at si nécessaire

    // Gestion des clés étrangères
";

    // Ajouter dynamiquement les relations de clés étrangères
    foreach ($foreignKeys as $foreignKey) {
        $modelTemplate .= "
    public function " . strtolower($foreignKey->foreignModel) . "()
    {
        return \$this->belongsTo(" . ucfirst($foreignKey->foreignModel) . "::class, '$foreignKey->foreignKey');
    }
";
    }

    $modelTemplate .= "}";

    return $modelTemplate;
}

}
