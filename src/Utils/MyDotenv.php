<?php
namespace App\Utils;

use Symfony\Component\Dotenv\Dotenv;

class MyDotenv {

    /**
     * Load the environment if the file is present.
     *
     * Necessary (?) because the .env file is not included in the
     * Docker image, so bootEnv throws an exception when the file isn't
     * found.
     *
     * Throws if the env isn't loaded.
     */
    public static function boot(string $path) {
        if (file_exists($path))
            (new Dotenv())->bootEnv($path);
        $e = $_ENV['APP_ENV'];
        $_SERVER['APP_ENV'] = $e;
        $_SERVER['APP_DEBUG'] = $e != 'prod';
        $df = $_ENV['DB_FILENAME'];  // This may throw if it's not set :-)
        if ($df == null || $df == '') {
            $msg = 'DB_FILENAME not set, env not loaded from existing ENV or ' . $path;
            throw new \Exception($msg);
        }
    }

}
