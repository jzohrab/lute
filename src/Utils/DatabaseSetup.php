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
        $newdbcreated = false;
        try {
            Connection::verifyConnectionParams();
            if (! Connection::databaseExists()) {
                Connection::createBlankDatabase();
                MigrationHelper::installBaseline();
                $newdbcreated = true;
                $messages[] = 'New database created.';
            }
            /*
            if (MigrationHelper::isLearningWithTextsDb()) {
            // todo
            }
            */
            if (MigrationHelper::hasPendingMigrations()) {
                MigrationHelper::runMigrations();
                if (! $newdbcreated) {
                    $messages[] = 'Database updated.';
                }
            }
        }
        catch (\Exception $e) {
            $args = ['errors' => [ $e->getMessage() ]];
            $error = DatabaseSetup::renderError('fatal_error.html.twig', $args);
        }

        return [ $messages, $error ];
    }

    private static function renderError($name, $args = []): string {
        // ref https://twig.symfony.com/doc/2.x/api.html
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../templates');
        $twig = new \Twig\Environment($loader);
        $template = $twig->load($name);
        return $template->render($args);
    }

}