<?php

namespace StormBin\Package\Views;

use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Compilers\BladeCompiler;

class Views
{
    protected static $instance;

    /**
     * Initialise l'instance du moteur Blade.
     */
    public static function init()
    {
        if (!self::$instance) {
            $views = dirname(__DIR__, 5) . '/Views'; // Dossier des vues
            $cache = dirname(__DIR__, 5) . '/storage/cache/views'; // Dossier de cache

            $filesystem = new Filesystem();
            $eventDispatcher = new Dispatcher();
            $viewFinder = new FileViewFinder($filesystem, [$views]);

            $engineResolver = new EngineResolver();

            // Compiler Blade
            $bladeCompiler = new BladeCompiler($filesystem, $cache);
            $engineResolver->register('blade', function () use ($bladeCompiler) {
                return new CompilerEngine($bladeCompiler);
            });

            // Ajouter le moteur PHP avec l'objet Filesystem
            $engineResolver->register('php', function () use ($filesystem) {
                return new PhpEngine($filesystem);
            });

            // Factory pour gérer les vues
            self::$instance = new Factory($engineResolver, $viewFinder, $eventDispatcher);
        }

        return self::$instance;
    }

    /**
     * Render une vue dynamique avec les données données.
     */
    public static function render($view, $data = [])
    {
        return self::init()->make($view, $data)->render();
    }

    /**
     * Render une vue avec un layout.
     */
    public static function renderWithLayout(string $layout, string $template, array $data = []): void
    {
        $content = self::getRenderedView($template, $data);
        $data['content'] = $content; // Ajouter le contenu de la vue
        echo self::render($layout, $data);
    }

    /**
     * Retourne le contenu d'une vue sous forme de string.
     */
    private static function getRenderedView(string $template, array $data = []): string
    {
        return self::init()->make($template, $data)->render();
    }

    /**
     * Redirige vers une URL.
     */
    public static function redirect(string $url): void
    {
        header("Location: " . filter_var($url, FILTER_SANITIZE_URL));
        exit();
    }

    /**
     * Retourne une réponse JSON.
     */
    public static function jsonResponse(array $data, int $status = 200): void
    {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code($status);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Définit un message flash en session.
     */
    public static function setFlash(string $key, string $message): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Récupère un message flash et le supprime.
     */
    public static function getFlash(string $key): ?string
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }

    /**
     * Définit un cookie sécurisé.
     */
    public static function setCookie(string $name, string $value, int $expire = 3600, string $path = "/", bool $secure = false, bool $httponly = true): void
    {
        setcookie($name, $value, time() + $expire, $path, "", $secure, $httponly);
    }

    /**
     * Récupère un cookie.
     */
    public static function getCookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Génère une page d'erreur HTTP.
     */
    public static function renderErrorPage(int $code = 404, string $message = "Page not found"): void
    {
        http_response_code($code);
        include __DIR__ . "/errors/{$code}.php";
        exit();
    }

    /**
     * Génère un fichier pour téléchargement.
     */
    public static function downloadFile(string $filePath, ?string $fileName = null): void
    {
        if (!file_exists($filePath)) {
            self::renderErrorPage(404, "Fichier non trouvé");
        }
        $fileName = $fileName ?: basename($filePath);
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit();
    }

    /**
     * Paginate un tableau de données.
     */
    public static function paginate(array $items, int $page = 1, int $perPage = 10): array
    {
        $total = count($items);
        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        return [
            'data' => array_slice($items, $offset, $perPage),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_pages' => $totalPages,
                'total_items' => $total
            ]
        ];
    }
}
