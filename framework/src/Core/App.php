<?php
    use StormBin\Package\Core\Config;
    use StormBin\Package\Database\Database;
    Config::loadEnv(__DIR__ . '/../../../../.env'); // Chemin a adapter
    Database::init();
    
    use Illuminate\Filesystem\FilesystemServiceProvider;

    $this->app->register(FilesystemServiceProvider::class);


    use Illuminate\Session\SessionServiceProvider;

    $this->app->register(SessionServiceProvider::class);
    
    