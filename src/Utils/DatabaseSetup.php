<?php

namespace App\Utils;

/**
 * Used by public/index.php to initiate and migrate the database.
 * 
 * Feels kind of messy, not sure where this belongs, but it will do
 * until proven poor.  ... only tested manually.
 */
class DatabaseSetup {

    /**
     * Returns [ messages, error string ]
     */
    public static function doSetup(): array {

        $messages = [];
        $error = null;
        try {
            Connection::verifyConnectionParams();
            if (! Connection::databaseExists()) {
                Connection::createBlankDatabase();
                MigrationHelper::installBaseline();
                $messages[] = [ 'New database created' ];
            }
            if (MigrationHelper::hasPendingMigrations()) {
                MigrationHelper::runMigrations();
                $messages[] = [ 'Database updated' ];
            }
        }
        catch (\Exception $e) {
            // ref https://twig.symfony.com/doc/2.x/api.html
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
            $twig = new \Twig\Environment($loader);
            $template = $twig->load('fatal_error.html.twig');
            $error = $template->render(['errors' => [ $e->getMessage(), 'something', 'here' ]]);
        }

        return [ $messages, $error ];
    }

}