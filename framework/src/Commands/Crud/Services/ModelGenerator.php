<?php

namespace StormBin\Package\Commands\Crud\Services;

use Illuminate\Database\Capsule\Manager as Capsule;

class ModelGenerator
{
    public function tableExists($tableName)
    {
        return Capsule::schema()->hasTable($tableName);
    }

    public function tableHasModel($tableName)
    {
        $modelPath = getcwd() . "/app/Models/" . ucfirst($tableName) . ".php";
        return file_exists($modelPath);
    }

    public function generateModel($tableName, $foreignKeys = [])
    {
        // Récupérer les colonnes de la table
        $columns = Capsule::schema()->getColumnListing($tableName);

        // Déterminer la clé primaire
        $primaryKey = $this->getPrimaryKey($tableName);

        // Déterminer si les timestamps existent
        $timestamps = $this->hasTimestamps($columns);

        // Générer le contenu du modèle
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

    private function getPrimaryKey($tableName)
    {
        $connection = Capsule::connection();
        $databaseType = $connection->getDriverName();

        if ($databaseType === 'mysql') {
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
        } elseif ($databaseType === 'sqlite') {
            // Pour SQLite : utiliser PRAGMA table_info
            $query = "PRAGMA table_info($tableName)";
            $result = $connection->select($query);

            foreach ($result as $row) {
                if ($row->pk) {
                    return $row->name;
                }
            }
        }

        return 'id'; // Par défaut, on suppose que la clé primaire est 'id'
    }

    private function hasTimestamps($columns)
    {
        return in_array('created_at', $columns) && in_array('updated_at', $columns);
    }

    private function generateModelContent($tableName, $primaryKey, $columns, $timestamps, $foreignKeys)
{
    $fillable = array_filter($columns, function($column) use ($primaryKey) {
        return $column !== 'created_at' && $column !== 'updated_at' && $column !== $primaryKey;
    });

    // Générer les règles de validation
    $validationRules = [];
    $validationMessages = [];

    foreach ($columns as $column) {
        if ($column === 'id' || $column === $primaryKey) {
            continue; // Ignorer la clé primaire
        }

        $rules = [];
        $messages = [];

        // Règles de base en fonction du type de colonne
        $columnDetails = $this->getColumnDetails($tableName, $column);
        $columnType = $columnDetails['type'] ?? 'string';

        if (strpos($columnType, 'int') !== false) {
            $rules[] = 'integer';
            $messages["$column.integer"] = "Le champ $column doit être un entier.";
        } elseif (strpos($columnType, 'varchar') !== false || strpos($columnType, 'text') !== false) {
            $rules[] = 'string';
            $messages["$column.string"] = "Le champ $column doit être une chaîne de caractères.";
        } elseif (strpos($columnType, 'date') !== false) {
            $rules[] = 'date';
            $messages["$column.date"] = "Le champ $column doit être une date valide.";
        }

        // Règles supplémentaires
        if ($columnDetails['nullable'] ?? false) {
            $rules[] = 'nullable';
        } else {
            $rules[] = 'required';
            $messages["$column.required"] = "Le champ $column est obligatoire.";
        }

        // Ajouter les règles et messages
        $validationRules[$column] = implode('|', $rules);
        $validationMessages = array_merge($validationMessages, $messages);
    }

    // Générer le contenu du modèle
    $modelTemplate = "<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class " . ucfirst($tableName) . " extends Model
{
    use HasFactory;

    // Définir le nom de la table
    protected \$table = '$tableName';

    // Définir la clé primaire
    protected \$primaryKey = '$primaryKey';

    // Champs remplissables
    protected \$fillable = [";

    // Ajouter les champs remplissables
    foreach ($fillable as $field) {
        $modelTemplate .= "'$field', ";
    }

    $modelTemplate = rtrim($modelTemplate, ', ') . "];

    // Timestamps
    public \$timestamps = " . ($timestamps ? 'true' : 'false') . ";

    // Règles de validation (protégées)
    protected static \$rules = " . var_export($validationRules, true) . ";

    // Messages d'erreur personnalisés (protégés)
    protected static \$messages = " . var_export($validationMessages, true) . ";

    // Relations
";

    // Ajouter les relations de clés étrangères
    foreach ($foreignKeys as $foreignKey) {
        // Ignorer les clés étrangères qui référencent la même table
        if (isset($foreignKey->foreignModel) && isset($foreignKey->foreignKey) && $foreignKey->foreignModel !== $tableName) {
            $modelTemplate .= "
    public function " . strtolower($foreignKey->foreignModel) . "()
    {
        return \$this->belongsTo(" . ucfirst($foreignKey->foreignModel) . "::class, '$foreignKey->foreignKey');
    }
";
        }
    }

    // Ajouter les méthodes publiques pour accéder aux règles et messages
    $modelTemplate .= "
    /**
     * Récupérer les règles de validation.
     */
    public static function getRules()
    {
        return static::\$rules;
    }

    /**
     * Récupérer les messages d'erreur personnalisés.
     */
    public static function getMessages()
    {
        return static::\$messages;
    }
}";

    return $modelTemplate;
}
private function getColumnDetails($tableName, $column)
{
    $connection = Capsule::connection();
    $databaseType = $connection->getDriverName();

    try {
        if ($databaseType === 'mysql') {
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
        } elseif ($databaseType === 'sqlite') {
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
        }
    } catch (\Exception $e) {
        // En cas d'erreur, retourner des valeurs par défaut
        return [
            'type' => 'string',
            'nullable' => false,
        ];
    }

    // Retourner des valeurs par défaut si aucune information n'est trouvée
    return [
        'type' => 'string',
        'nullable' => false,
    ];
}
}