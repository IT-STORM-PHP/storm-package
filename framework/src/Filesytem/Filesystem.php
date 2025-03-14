<?php

namespace StormBin\Package\Filesytem;

class Filesystem
{
    /**
     * Lire le contenu d'un fichier.
     *
     * @param string $path Chemin du fichier.
     * @return string Contenu du fichier.
     */
    public function get($path)
    {
        return file_get_contents($path);
    }

    /**
     * Ajouter du contenu à un fichier.
     *
     * @param string $path Chemin du fichier.
     * @param string $content Contenu à ajouter.
     */
    public function append($path, $content)
    {
        file_put_contents($path, $content, FILE_APPEND);
    }
}