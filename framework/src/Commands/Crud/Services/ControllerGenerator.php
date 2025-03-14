<?php

namespace StormBin\Package\Commands\Crud\Services;

class ControllerGenerator
{
    protected $modelName;
    protected $controllerName;
    protected $foreignKeys;
    protected $isApi;

    public function __construct($modelName, $foreignKeys = [], $isApi = false)
    {
        $this->modelName = $modelName;
        $this->controllerName = $modelName . 'Controller';
        $this->foreignKeys = $foreignKeys;
        $this->isApi = $isApi;
    }

    public function generateController()
    {
        $controllerCode = $this->generateControllerCode();
        $this->saveControllerFile($controllerCode);
    }

    private function generateControllerCode()
{
    $modelName = $this->modelName;
    $controllerName = $this->controllerName;

    // Générer les imports des modèles associés aux clés étrangères
    $imports = [];
    foreach ($this->foreignKeys as $foreignKey) {
        if (isset($foreignKey->foreignModel)) {
            $imports[] = "use App\Models\\" . ucfirst($foreignKey->foreignModel) . ";";
        }
    }
    $imports = implode("\n", $imports);

    // Générer les relations à charger avec with()
    $withRelations = $this->generateWithRelations();

    // Charger le fichier stub approprié
    $stubFile = $this->isApi ? 'ControllerApi.stub' : 'ControllerWeb.stub';
    $stubPath = dirname(__DIR__, 3) . "/StubFiles/Crud/Controllers/{$stubFile}";

    if (!file_exists($stubPath)) {
        throw new \Exception("Stub file not found: {$stubPath}");
    }

    $stubContent = file_get_contents($stubPath);

    // Remplacer les placeholders dans le stub
    $replacements = [
        '%modelName%' => $modelName,
        '%controllerName%' => $controllerName,
        '%imports%' => $imports,
        '%withRelations%' => $withRelations,
        '%modelNameLower%' => strtolower($modelName),
    ];

    $stubContent = str_replace(array_keys($replacements), array_values($replacements), $stubContent);

    return $stubContent;
}

    private function generateWithRelations()
    {
        $relations = [];
        foreach ($this->foreignKeys as $foreignKey) {
            if (isset($foreignKey->foreignModel)) {
                $relations[] = "'" . strtolower($foreignKey->foreignModel) . "'";
            }
        }
        return implode(', ', $relations);
    }

    private function saveControllerFile($controllerCode)
{
    $basePath = getcwd() . "/app/";
    $controllerPath = $this->isApi
        ? $basePath . "api/Controllers/{$this->controllerName}.php"
        : $basePath . "web/Controllers/{$this->controllerName}.php";

    // Créer le dossier Controllers s'il n'existe pas
    if (!is_dir(dirname($controllerPath))) {
        mkdir(dirname($controllerPath), 0755, true);
    }

    // Sauvegarder le contrôleur
    if (!file_exists($controllerPath)) {
        file_put_contents($controllerPath, $controllerCode);
        echo "Contrôleur {$this->controllerName} créé avec succès dans " . ($this->isApi ? "api" : "web") . ".\n";
    } else {
        echo "Le contrôleur {$this->controllerName} existe déjà.\n";
    }
}
}