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
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class Views
{
    protected static $instance;

    /**
     * Initialise l'instance du moteur Blade.
     */
    public static function init()
    {
        if (!self::$instance) {
            $views = dirname(__DIR__, 6) . '/Views'; // Dossier des vues
            $cache = dirname(__DIR__, 6) . '/storage/cache/views'; // Dossier de cache

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
     * Redirige vers une URL.
     */
    public static function redirect(string $url = null, int $status = 302, array $headers = []): void
{
    // Appliquer les en-têtes personnalisés si nécessaires
    foreach ($headers as $key => $value) {
        header("$key: $value", true);
    }

    // Redirection propre
    header("Location: $url", true, $status);
    exit; // ⚠️ Important : empêche toute sortie supplémentaire
}

    /**
     * Redirige vers la page précédente.
     */
    public static function back(int $status = 302, array $headers = []): RedirectResponse
    {
        return Redirect::back($status, $headers);
    }

    /**
     * Définit un message flash en session.
     */
    public static function with(string $key, string $message): void
    {
        Session::flash($key, $message);
    }

    /**
     * Définit des messages d'erreur en session.
     */
    public static function withErrors(array $errors): RedirectResponse
    {
        return Redirect::back()->withErrors($errors);
    }

    /**
     * Conserve les données soumises en session.
     */
    public static function withInput(): RedirectResponse
    {
        return Redirect::back()->withInput();
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
 * Retourne une réponse JSON.
 *
 * @param mixed $data Les données à retourner.
 * @param int $status Le code de statut HTTP (par défaut 200).
 * @param array $headers Les en-têtes HTTP personnalisés (optionnel).
 * @param int $options Les options JSON (par défaut JSON_PRETTY_PRINT).
 * @return string|JsonResponse
 */
public static function jsonResponse($data, int $status = 200, array $headers = [], int $options = JSON_PRETTY_PRINT)
{
    // En-têtes par défaut
    $defaultHeaders = [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'no-cache, private',
    ];

    // Fusionner les en-têtes personnalisés avec les en-têtes par défaut
    $finalHeaders = array_merge($defaultHeaders, $headers);

    // Si l'utilisateur ne veut pas d'en-têtes HTTP, retourner uniquement les données JSON
    if (empty($headers)) {
        return json_encode($data, $options);
    }

    // Sinon, retourner une réponse JSON avec les en-têtes
    return new JsonResponse($data, $status, $finalHeaders, $options);
}
    /**
     * Récupère un message flash et le supprime.
     */
    public static function getFlash(string $key): ?string
    {
        return Session::get($key);
    }

    /**
     * Définit un cookie sécurisé.
     */
    public static function cookie(string $name, string $value, int $minutes = 0, string $path = "/", string $domain = null, bool $secure = false, bool $httpOnly = true)
    {
        return Cookie::make($name, $value, $minutes, $path, $domain, $secure, $httpOnly);
    }

    /**
     * Récupère un cookie.
     */
    public static function getCookie(string $name): ?string
    {
        return Cookie::get($name);
    }

    /**
     * Génère une page d'erreur HTTP.
     */
    public static function abort(int $code = 404, string $message = "Page not found")
    {
        return Response::abort($code, $message);
    }

    /**
     * Génère un fichier pour téléchargement.
     */
    public static function download(string $filePath, ?string $fileName = null, array $headers = [])
    {
        return Response::download($filePath, $fileName, $headers);
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

    /**
     * Retourne une réponse HTTP.
     */
    public static function response($content = '', int $status = 200, array $headers = [])
    {
        return Response::make($content, $status, $headers);
    }

    /**
     * Retourne une réponse de redirection.
     */
    public static function redirectResponse(string $url, int $status = 302, array $headers = [])
    {
        return Redirect::to($url, $status, $headers);
    }

    /**
     * Retourne une réponse de vue.
     */
    public static function viewResponse(string $view, array $data = [], int $status = 200, array $headers = [])
    {
        return Response::view($view, $data, $status, $headers);
    }
}
