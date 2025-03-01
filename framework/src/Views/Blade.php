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

class Blade
{
    protected static $instance;

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

    public static function render($view, $data = [])
    {
        return self::init()->make($view, $data)->render();
    }
}
