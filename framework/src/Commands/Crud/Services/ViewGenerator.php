<?php

namespace StormBin\Package\Commands\Crud\Services;

use Illuminate\Database\Capsule\Manager as Capsule;

class ViewGenerator
{
    protected $tableName;
    protected $columns;
    protected $foreignKeys;

    public function __construct($tableName, $columns, $foreignKeys)
    {
        $this->tableName = $tableName;
        $this->columns = $columns;
        $this->foreignKeys = $foreignKeys;
    }

    public function generateViews()
    {
        // Chemin des fichiers stub
        $stubPath = dirname(__DIR__, 3) . "/StubFiles/Crud/Views/";

        // Créer le dossier des vues s'il n'existe pas
        $viewPath = getcwd() . "/Views/" . ucfirst($this->tableName);
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0755, true);
        }

        // Générer les vues CRUD
        $this->generateIndexView($viewPath, $stubPath);
        $this->generateCreateView($viewPath, $stubPath);
        $this->generateEditView($viewPath, $stubPath);
        $this->generateShowView($viewPath, $stubPath);
        $this->generateErrorsView($viewPath, $stubPath);
    }

    protected function generateIndexView($viewPath, $stubPath)
    {
        $stub = file_get_contents($stubPath . 'index.stub');
        $stub = str_replace('{{TableName}}', ucfirst($this->tableName), $stub);
        $stub = str_replace('{{Columns}}', $this->generateTableHeaders(), $stub);
        $stub = str_replace('{{Rows}}', $this->generateTableRows(), $stub);

        file_put_contents($viewPath . '/index.blade.php', $stub);
        echo "Vue index créée : $viewPath/index.blade.php\n";
    }

    protected function generateCreateView($viewPath, $stubPath)
    {
        $stub = file_get_contents($stubPath . 'create.stub');
        $stub = str_replace('{{TableName}}', ucfirst($this->tableName), $stub);
        $stub = str_replace('{{FormFields}}', $this->generateFormFields(), $stub);

        file_put_contents($viewPath . '/create.blade.php', $stub);
        echo "Vue create créée : $viewPath/create.blade.php\n";
    }

    protected function generateEditView($viewPath, $stubPath)
    {
        $stub = file_get_contents($stubPath . 'edit.stub');
        $stub = str_replace('{{TableName}}', ucfirst($this->tableName), $stub);
        $stub = str_replace('{{FormFields}}', $this->generateFormFields(true), $stub);

        file_put_contents($viewPath . '/edit.blade.php', $stub);
        echo "Vue edit créée : $viewPath/edit.blade.php\n";
    }

    protected function generateShowView($viewPath, $stubPath)
    {
        $stub = file_get_contents($stubPath . 'show.stub');
        $stub = str_replace('{{TableName}}', ucfirst($this->tableName), $stub);
        $stub = str_replace('{{Fields}}', $this->generateShowFields(), $stub);

        file_put_contents($viewPath . '/show.blade.php', $stub);
        echo "Vue show créée : $viewPath/show.blade.php\n";
    }
    protected function generateErrorsView($viewPath, $stubPath){
        $stub = file_get_contents($stubPath . 'errors.stub');
        $stub = str_replace('{{TableName}}', ucfirst($this->tableName), $stub);
        $stub = str_replace('{{FormFields}}', $this->generateFormFields(true), $stub);

        file_put_contents($viewPath . '/errors.blade.php', $stub);
        echo "Vue errors créée : $viewPath/errors.blade.php\n";
    }

    protected function generateTableHeaders()
    {
        $headers = '';
        foreach ($this->columns as $column) {
            $headers .= "<th>" . ucfirst($column) . "</th>\n";
        }
        return $headers;
    }

    protected function generateTableRows()
    {
        $rows = '';
        foreach ($this->columns as $column) {
            $rows .= "<td>{{ \$item->$column }}</td>\n";
        }
        return $rows;
    }

    protected function generateFormFields($isEdit = false)
    {
        $fields = '';
        foreach ($this->columns as $column) {
            if ($column === 'id' || $column === 'created_at' || $column === 'updated_at') {
                continue;
            }

            $fields .= "<div class='form-group'>\n";
            $fields .= "    <label for='$column'>" . ucfirst($column) . "</label>\n";
            $fields .= "    <input type='text' name='$column' id='$column' class='form-control' value='{{ " . ($isEdit ? "\$item->$column" : "old('$column')") . " }}'>\n";
            $fields .= "</div>\n";
        }
        return $fields;
    }

    protected function generateShowFields()
    {
        $fields = '';
        foreach ($this->columns as $column) {
            $fields .= "<tr>\n";
            $fields .= "    <th>" . ucfirst($column) . "</th>\n";
            $fields .= "    <td>{{ \$item->$column }}</td>\n";
            $fields .= "</tr>\n";
        }
        return $fields;
    }
    
}