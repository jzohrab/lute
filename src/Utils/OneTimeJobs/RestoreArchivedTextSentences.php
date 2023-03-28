<?php

namespace App\Utils\OneTimeJobs;

require __DIR__.'/../../../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Text;
use App\Utils\Connection;

/**
 * Restore sentences for archived texts.
 *
 * Previously, archived texts used to delete their sentences and
 * textitem2 entries, but that makes looking for references
 * (Dictionary->findReferences()) less useful.  It's minimal space, so
 * now sentences are left alone.
 */
class RestoreArchivedTextSentences {

    public static function do_restore($showecho = true) {
        $echo = function($s) use ($showecho) {
            if ($showecho)
                echo $s;
        };
        $echo("Preparing environment ...\n");
        (new Dotenv())->load(__DIR__.'/../../../.env.local');
        $kernel = new Kernel($_SERVER['APP_ENV'], true);
        $echo("Booting ... ");
        $kernel->boot();
        $echo("done.\n");
        $repo = $kernel->getContainer()->get('doctrine.orm.entity_manager')->getRepository(Text::class);

        $texts = array_filter($repo->findAll(), fn($t) => $t->isArchived());
        $conn = Connection::getFromEnvironment();
        $c = count($texts);
        $echo("Restoring sentences for {$c} archived text(s), as needed.\n");
        foreach ($texts as $t) {
            $sql = "select count(*) as c from sentences where SeTxID = {$t->getID()}";
            $c = $conn->query($sql)->fetch_array();
            if ($c['c'] == '0') {
                $echo("  {$t->getTitle()}\n");
                $t->parse();
            }
        }
        $echo("Done.\n");
    }
}

?>