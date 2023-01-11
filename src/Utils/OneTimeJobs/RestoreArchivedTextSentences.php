<?php

namespace App\Utils\OneTimeJobs;

require __DIR__.'/../../../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Text;

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
        $c = count($texts);
        $echo("Restoring sentences for {$c} archived text(s).\n");
        foreach ($texts as $t) {
            $echo("  {$t->getTitle()}\n");
            $repo->save($t, true);
        }
        $echo("Done.\n");
    }
}

?>