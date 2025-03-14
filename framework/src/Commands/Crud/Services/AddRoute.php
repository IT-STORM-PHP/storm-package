<?php

namespace StormBin\Package\Commands\Crud\Services;

use StormBin\Package\Filesytem\Filesystem;

class AddRoute
{
    protected $filesystem;
    protected $isApi;

    public function __construct($isApi = false)
    {
        $this->filesystem = new Filesystem();
        $this->isApi = $isApi;
    }

    /**
     * Ajoute les routes au fichier routes/web.php.
     *
     * @param string $modelName Le nom du modèle (par exemple, "Tache").
     * @throws \Exception Si le fichier stub ou routes/web.php est introuvable.
     */
    public function addRoutes($modelName)
    {
        // Convertir le nom du modèle en minuscules pour les routes
        $routeName = strtolower($modelName);

        // Déterminer le fichier stub à utiliser
        $stubFile = $this->isApi ? 'ControllerApi.stub' : 'ControllerWeb.stub';
        $stubPath = dirname(__DIR__, 3) . "/StubFiles/Crud/Routes/{$stubFile}";

        // Vérifier si le fichier stub existe
        if (!file_exists($stubPath)) {
            throw new \Exception("Le fichier stub '{$stubFile}' est introuvable dans : {$stubPath}");
        }

        // Lire le contenu du fichier stub
        $stubContent = file_get_contents($stubPath);

        // Remplacer les placeholders dans le stub
        $routeContent = str_replace(
            ['%modelName%', '%routeName%'],
            [$modelName, $routeName],
            $stubContent
        );

        // Chemin du fichier routes/web.php
        $routesFilePath = getcwd() . '/routes/web.php';

        // Vérifier si le fichier routes/web.php existe
        if (!file_exists($routesFilePath)) {
            throw new \Exception("Le fichier 'routes/web.php' est introuvable dans : {$routesFilePath}");
        }

        // Ajouter les routes au fichier routes/web.php
        file_put_contents($routesFilePath, $routeContent, FILE_APPEND);
        echo "Routes ajoutées avec succès pour le modèle '$modelName'.\n";
    }
}