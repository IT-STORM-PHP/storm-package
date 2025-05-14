<?php

$envPath = dirname(__DIR__, 2) . '/.env'; // cela remonte de /framework/src/Core -> /espace-ifedu/.env

    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
    else {
        throw new Exception("Le fichier .env n'existe pas à l'emplacement spécifié : $envPath");
    }
