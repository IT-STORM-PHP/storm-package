<?php
    use StormBin\Package\Core\Config;
    use StormBin\Package\Database\Database;
    Config::loadEnv(__DIR__ . '/../../../../.env'); // Chemin a adapter
    Database::init();
    