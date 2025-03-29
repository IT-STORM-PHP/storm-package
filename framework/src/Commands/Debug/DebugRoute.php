<?php

namespace StormBin\Package\Commands\Debug;

use StormBin\Package\Routes\Route;

class DebugRoute
{
    public function handle()
    {
        // Charger les routes depuis le fichier web.php
        require getcwd() . '/routes/web.php';

        // Récupérer les routes enregistrées
        $routes = Route::getRoutes();

        // Afficher les routes dans un tableau
        $this->displayRoutes($routes);
    }

    protected function displayRoutes(array $routes)
    {
        // Tableau pour stocker les données des routes
        $tableData = [];

        // En-têtes du tableau
        $tableData[] = ['Méthode', 'Nom de la route', 'Classe appelée', 'Méthode exécutée', 'Route donnée par l\'utilisateur'];

        // Remplir le tableau avec les données des routes
        foreach ($routes as $method => $routeList) {
            foreach ($routeList as $uri => $action) {
                // Extraire la classe et la méthode
                if (is_array($action) && count($action) === 2) {
                    // Cas où l'action est un tableau [Controller, méthode]
                    [$controller, $methodName] = $action;
                    $className = is_object($controller) ? get_class($controller) : $controller;
                } elseif (is_string($action)) {
                    // Cas où l'action est une chaîne "Controller@méthode"
                    [$className, $methodName] = explode('@', $action);
                } else {
                    // Cas par défaut (Closure ou autre)
                    $className = 'Closure';
                    $methodName = 'Closure';
                }

                // Récupérer le nom de la route
                $routeName = Route::getRouteName($uri);

                // Ajouter les données au tableau
                $tableData[] = [
                    $method,
                    $routeName ?? 'N/A',
                    $className,
                    $methodName,
                    $uri,
                ];
            }
        }

        // Calculer la largeur maximale de chaque colonne
        $columnWidths = [];
        foreach ($tableData as $row) {
            foreach ($row as $columnIndex => $cell) {
                $columnWidths[$columnIndex] = max($columnWidths[$columnIndex] ?? 0, strlen($cell));
            }
        }

        // Afficher le tableau
        $this->renderTable($tableData, $columnWidths);
    }

    protected function renderTable(array $tableData, array $columnWidths)
    {
        // Afficher l'en-tête
        echo "\n\x1b[32mListe des routes :\x1b[0m\n";
        echo $this->renderTableRow($tableData[0], $columnWidths, true);

        // Afficher la ligne de séparation
        echo $this->renderTableSeparator($columnWidths);

        // Afficher les données
        for ($i = 1; $i < count($tableData); $i++) {
            echo $this->renderTableRow($tableData[$i], $columnWidths);
        }

        // Afficher la ligne de séparation finale
        echo $this->renderTableSeparator($columnWidths);
    }

    protected function renderTableRow(array $row, array $columnWidths, bool $isHeader = false)
    {
        $formattedRow = '';
        foreach ($row as $columnIndex => $cell) {
            // Ajouter un espace de chaque côté de la cellule
            $formattedCell = ' ' . str_pad($cell, $columnWidths[$columnIndex]) . ' ';

            // Ajouter une couleur différente pour l'en-tête
            if ($isHeader) {
                $formattedCell = "\x1b[34m" . $formattedCell . "\x1b[0m";
            }

            // Ajouter un séparateur entre les colonnes
            $formattedRow .= $formattedCell . '|';
        }

        // Retourner la ligne formatée
        return rtrim($formattedRow, '|') . "\n";
    }

    protected function renderTableSeparator(array $columnWidths)
    {
        $separator = '';
        foreach ($columnWidths as $width) {
            $separator .= '+' . str_repeat('-', $width + 2); // +2 pour les espaces ajoutés
        }
        return $separator . "+\n";
    }
}