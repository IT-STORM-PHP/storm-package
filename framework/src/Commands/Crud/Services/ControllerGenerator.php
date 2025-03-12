<?php

namespace StormBin\Package\Commands\Crud\Services;

class ControllerGenerator
{
    protected $modelName;
    protected $controllerName;
    protected $foreignKeys;

    public function __construct($modelName, $foreignKeys = [])
    {
        $this->modelName = $modelName;
        $this->controllerName = $modelName . 'Controller';
        $this->foreignKeys = $foreignKeys;
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

    $code = <<<PHP
<?php

namespace App\Web\Controllers;

use StormBin\Package\Controllers\Controller;
use StormBin\Package\Views\Views;
use App\Models\\$modelName;
use StormBin\Package\Request\Request;
use Illuminate\Validation\ValidationException;
$imports

class $controllerName extends Controller
{
    /**
     * Afficher la liste des ressources.
     */
    public function index()
    {
        \$items = $modelName::with([$withRelations])->get();
        Views::jsonResponse(\$items->toArray());
    }

    /**
     * Afficher le formulaire de création d'une nouvelle ressource.
     */
    public function create()
    {
        Views::jsonResponse(['message' => 'Formulaire de création']);
    }

    /**
     * Stocker une nouvelle ressource dans la base de données.
     */
    public function store(Request \$request)
    {
        try {
            // Valider les données de la requête
            \$validatedData = \$request->validate($modelName::getRules(), $modelName::getMessages());

            // Créer une nouvelle entité avec les données validées
            \$item = $modelName::create(\$validatedData);

            // Retourner une réponse JSON
            Views::jsonResponse(['message' => 'Création réussie', 'data' => \$item], 201);
        } catch (ValidationException \$e) {
            // Retourner les erreurs de validation
            Views::jsonResponse(['errors' => \$e->errors()], 422);
        }
    }

    /**
     * Afficher une ressource spécifique.
     */
    public function show(\$id)
    {
        \$item = $modelName::with([$withRelations])->findOrFail(\$id);
        Views::jsonResponse(\$item->toArray());
    }

    /**
     * Afficher le formulaire de modification d'une ressource.
     */
    public function edit(\$id)
    {
        \$item = $modelName::with([$withRelations])->findOrFail(\$id);
        Views::jsonResponse(['message' => 'Formulaire de modification', 'data' => \$item->toArray()]);
    }

    /**
     * Mettre à jour une ressource dans la base de données.
     */
    public function update(Request \$request, \$id)
    {
        try {
            // Valider les données de la requête
            \$validatedData = \$request->validate($modelName::getRules(), $modelName::getMessages());

            // Trouver l'entité à mettre à jour
            \$item = $modelName::findOrFail(\$id);

            // Mettre à jour l'entité avec les données validées
            \$item->update(\$validatedData);

            // Retourner une réponse JSON
            Views::jsonResponse(['message' => 'Mise à jour réussie', 'data' => \$item->toArray()]);
        } catch (ValidationException \$e) {
            // Retourner les erreurs de validation
            Views::jsonResponse(['errors' => \$e->errors()], 422);
        }
    }

    /**
     * Supprimer une ressource de la base de données.
     */
    public function destroy(\$id)
    {
        \$item = $modelName::findOrFail(\$id);
        \$item->delete();
        Views::jsonResponse(['message' => 'Suppression réussie']);
    }
}
PHP;

    return $code;
}

    /**
     * Générer les relations à charger avec with().
     */
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
        $controllerPath = getcwd() . "/app/web/Controllers/{$this->controllerName}.php";

        // Créer le dossier Controllers s'il n'existe pas
        if (!is_dir(dirname($controllerPath))) {
            mkdir(dirname($controllerPath), 0755, true);
        }

        // Sauvegarder le contrôleur
        if (!file_exists($controllerPath)) {
            file_put_contents($controllerPath, $controllerCode);
            echo "Contrôleur {$this->controllerName} créé avec succès.\n";
        } else {
            echo "Le contrôleur {$this->controllerName} existe déjà.\n";
        }
    }
}