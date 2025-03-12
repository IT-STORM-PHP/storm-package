<?php

namespace StormBin\Package\Commands\Crud\Services;

use Illuminate\Database\Capsule\Manager as Capsule;

class ForeignKeyHandler
{
    public function getForeignKeys($tableName)
{
    $connection = Capsule::connection();
    $databaseType = $connection->getDriverName();

    if ($databaseType === 'mysql') {
        // Pour MySQL
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
        // Pour PostgreSQL
        $query = "
            SELECT 
                kcu.column_name AS foreignKey,
                ccu.table_name AS foreignModel
            FROM 
                information_schema.key_column_usage AS kcu
            JOIN 
                information_schema.constraint_column_usage AS ccu
            ON 
                kcu.constraint_name = ccu.constraint_name
            WHERE 
                kcu.table_name = ? 
                AND ccu.table_name IS NOT NULL
        ";
        $result = $connection->select($query, [$tableName]);

        // Filtrer les résultats pour ignorer les clés étrangères qui référencent la même table
        $filteredResults = array_filter($result, function($row) use ($tableName) {
            return $row->foreignModel !== $tableName;
        });

        // Ajouter un débogage pour vérifier les résultats filtrés
        if (empty($filteredResults)) {
            echo "Aucune clé étrangère valide trouvée pour la table '$tableName'.\n";
        } else {
            echo "Clés étrangères valides trouvées pour la table '$tableName' :\n";
            print_r($filteredResults);
        }

        return $filteredResults;
    } elseif ($databaseType === 'sqlite') {
        // Pour SQLite
        $query = "PRAGMA foreign_key_list($tableName)";
        $result = $connection->select($query);

        $foreignKeys = [];
        foreach ($result as $row) {
            $foreignKeys[] = (object) [
                'foreignKey' => $row->from,
                'foreignModel' => $row->table,
            ];
        }
        return $foreignKeys;
    }

    return [];
}
}