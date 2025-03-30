<?php

namespace StormBin\Package\Commands\Crud\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Str;

class ViewGenerator
{
    protected $tableName;
    protected $columns;
    protected $foreignKeys;
    protected $modelName;
    protected $enums = [];

    public function __construct($tableName, $columns, $foreignKeys)
    {
        $this->tableName = $tableName;
        $this->modelName = Str::singular(ucfirst($tableName));
        $this->columns = $this->enhanceColumns($columns);
        $this->foreignKeys = is_array($foreignKeys) ? $foreignKeys : [];
        $this->detectEnums();
    }

    protected function detectEnums()
    {
        try {
            $connection = Capsule::connection();
            $databaseType = $connection->getDriverName();

            if ($databaseType === 'mysql') {
                $result = $connection->select("SHOW COLUMNS FROM {$this->tableName}");
                foreach ($result as $column) {
                    if (strpos($column->Type, 'enum') === 0) {
                        preg_match("/^enum\(\'(.*)\'\)$/", $column->Type, $matches);
                        $options = explode("','", $matches[1]);
                        $this->enums[$column->Field] = $options;
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if enum detection doesn't work
        }
    }

    protected function enhanceColumns($columns)
    {
        if (!is_array($columns)) {
            return [];
        }

        return array_map(function($column) {
            $details = $this->getColumnDetails($this->tableName, $column);
            $isForeignKey = $this->isForeignKey($column);
            $isEnum = isset($this->enums[$column]);
            
            return [
                'name' => $column,
                'type' => $details['type'],
                'nullable' => $details['nullable'],
                'input_type' => $this->mapTypeToInput($details['type'], $column, $isEnum),
                'is_foreign_key' => $isForeignKey,
                'is_enum' => $isEnum,
                'enum_values' => $isEnum ? $this->enums[$column] : null,
                'relation' => $isForeignKey ? $this->getRelationInfo($column) : null
            ];
        }, $columns);
    }

    protected function getColumnDetails($tableName, $column)
    {
        $connection = Capsule::connection();
        $databaseType = $connection->getDriverName();

        try {
            if ($databaseType === 'mysql') {
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
                $query = "SELECT data_type, is_nullable FROM information_schema.columns WHERE table_name = ? AND column_name = ?";
                $result = $connection->select($query, [$tableName, $column]);

                if (!empty($result)) {
                    return [
                        'type' => $result[0]->data_type,
                        'nullable' => $result[0]->is_nullable === 'YES',
                    ];
                }
            } elseif ($databaseType === 'sqlite') {
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
            return [
                'type' => 'string',
                'nullable' => false,
            ];
        }

        return [
            'type' => 'string',
            'nullable' => false,
        ];
    }

    protected function mapTypeToInput($dbType, $columnName, $isEnum)
    {
        if ($isEnum) {
            return 'select';
        }
        if ($this->isForeignKey($columnName)) {
            return 'select';
        }
        if (strpos($dbType, 'int') !== false) {
            return 'number';
        }
        if (strpos($dbType, 'text') !== false) {
            return 'textarea';
        }
        if (strpos($dbType, 'date') !== false) {
            return 'date';
        }
        if (strpos($dbType, 'tinyint(1)') !== false) {
            return 'checkbox';
        }
        return 'text';
    }

    protected function isForeignKey($column)
    {
        if (!is_array($this->foreignKeys)) {
            return false;
        }

        foreach ($this->foreignKeys as $fk) {
            if (is_object($fk) && property_exists($fk, 'column_name') && $fk->column_name === $column) {
                return true;
            }
        }
        return false;
    }

    protected function getRelationInfo($column)
    {
        foreach ($this->foreignKeys as $fk) {
            if (is_object($fk) && 
                property_exists($fk, 'column_name') && 
                $fk->column_name === $column &&
                property_exists($fk, 'referenced_table')) 
            {
                return [
                    'model' => Str::singular(ucfirst($fk->referenced_table)),
                    'table' => $fk->referenced_table,
                    'key' => property_exists($fk, 'referenced_column') ? $fk->referenced_column : 'id',
                    'display' => 'name' // Default display field
                ];
            }
        }
        return null;
    }

    public function generateViews()
    {
        $stubPath = dirname(__DIR__, 3) . "/StubFiles/Crud/Views/";
        $viewPath = getcwd() . "/Views/" . ucfirst($this->tableName) . "/";
        
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        $views = ['index', 'create', 'edit', 'show'];
        foreach ($views as $view) {
            $this->generateView($view, $viewPath, $stubPath);
        }
    }

    protected function generateView($type, $viewPath, $stubPath)
    {
        $stubFile = $stubPath . $type . '.stub';
        if (!file_exists($stubFile)) {
            echo "Fichier stub manquant : $stubFile\n";
            return;
        }

        $stub = file_get_contents($stubFile);
        
        $replacements = [
            '{{TableName}}' => $this->tableName,
            '{{ModelName}}' => $this->modelName,
            '{{ModelNamePlural}}' => Str::plural($this->modelName),
            '{{TableHeaders}}' => $this->generateTableHeaders(),
            '{{TableRows}}' => $this->generateTableRows(),
            '{{FormFields}}' => $this->generateFormFields($type === 'edit'),
            '{{ShowFields}}' => $this->generateShowFields(),
            '{{Relations}}' => $this->generateRelations()
        ];

        foreach ($replacements as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        file_put_contents("$viewPath/$type.blade.php", $stub);
        echo "Vue $type créée : $viewPath/$type.blade.php\n";
    }

    protected function generateTableHeaders()
    {
        $headers = '';
        foreach ($this->columns as $column) {
            if (!in_array($column['name'], ['created_at', 'updated_at'])) {
                $headers .= "<th>" . $this->formatLabel($column['name']) . "</th>\n";
            }
        }
        return $headers;
    }

    protected function generateTableRows()
    {
        $rows = '';
        foreach ($this->columns as $column) {
            if (!in_array($column['name'], ['created_at', 'updated_at'])) {
                if ($column['is_foreign_key'] && isset($column['relation'])) {
                    $rows .= "<td>{{ \$item->{$column['relation']['table']}->{$column['relation']['display']} ?? 'N/A' }}</td>\n";
                } else {
                    $rows .= "<td>{{ \$item->{$column['name']} }}</td>\n";
                }
            }
        }
        return $rows;
    }

    protected function generateFormFields($forEdit = false)
    {
        $fields = '';
        foreach ($this->columns as $column) {
            if (in_array($column['name'], ['id', 'created_at', 'updated_at'])) {
                continue;
            }

            if ($column['is_enum']) {
                $fields .= $this->generateEnumField($column, $forEdit);
            } elseif ($column['is_foreign_key']) {
                $fields .= $this->generateRelationField($column, $forEdit);
            } else {
                $fields .= $this->generateStandardField($column, $forEdit);
            }
        }
        return $fields;
    }

    protected function generateEnumField($column, $forEdit)
    {
        $value = $forEdit ? "\$item->{$column['name']}" : "old('{$column['name']}')";
        $options = '';
        
        foreach ($column['enum_values'] as $enumValue) {
            $selected = "{{ $value === '$enumValue' ? 'selected' : '' }}";
            $options .= "<option value='$enumValue' $selected>$enumValue</option>\n";
        }

        return "<div class='form-group'>
    <label for='{$column['name']}'>".$this->formatLabel($column['name'])."</label>
    <select name='{$column['name']}' id='{$column['name']}' class='form-control'>
        <option value=''>Sélectionnez...</option>
        $options
    </select>
</div>\n";
    }

    protected function generateRelationField($column, $forEdit)
    {
        if (!isset($column['relation'])) {
            return '';
        }

        $relation = $column['relation'];
        $selected = $forEdit ? "\$item->{$column['name']} == \$rel->id ? 'selected'" : "false";
        
        return "<div class='form-group'>
    <label for='{$column['name']}'>".$this->formatLabel($column['name'])."</label>
    <select name='{$column['name']}' id='{$column['name']}' class='form-control'>
        <option value=''>Sélectionnez...</option>
        @foreach(\${$relation['table']} as \$rel)
            <option value='{\$rel->id}' {{ $selected }}>
                {\$rel->{$relation['display']}}
            </option>
        @endforeach
    </select>
</div>\n";
    }

    protected function generateStandardField($column, $forEdit)
    {
        $value = $forEdit ? "\$item->{$column['name']}" : "old('{$column['name']}')";
        $inputType = $column['input_type'];
        
        if ($inputType === 'textarea') {
            $input = "<textarea name='{$column['name']}' id='{$column['name']}' class='form-control'>{{ $value }}</textarea>";
        } else {
            $input = "<input type='$inputType' name='{$column['name']}' id='{$column['name']}' class='form-control' value='{{ $value }}'>";
        }

        return "<div class='form-group'>
    <label for='{$column['name']}'>".$this->formatLabel($column['name'])."</label>
    $input
</div>\n";
    }

    protected function generateShowFields()
    {
        $fields = '';
        foreach ($this->columns as $column) {
            $displayValue = $column['is_foreign_key'] && isset($column['relation'])
                ? "\$item->{$column['relation']['table']}->{$column['relation']['display']} ?? 'N/A'"
                : "\$item->{$column['name']}";
            
            $fields .= "<tr>
    <th>".$this->formatLabel($column['name'])."</th>
    <td>{{ $displayValue }}</td>
</tr>\n";
        }
        return $fields;
    }

    protected function generateRelations()
    {
        $relations = [];
        foreach ($this->foreignKeys as $fk) {
            if (is_object($fk) && property_exists($fk, 'referenced_table')) {
                $relations[] = [
                    'name' => Str::plural($fk->referenced_table),
                    'model' => Str::singular(ucfirst($fk->referenced_table)),
                    'table' => $fk->referenced_table
                ];
            }
        }
        return var_export($relations, true);
    }

    protected function formatLabel($name)
    {
        return ucfirst(str_replace('_', ' ', $name));
    }
}