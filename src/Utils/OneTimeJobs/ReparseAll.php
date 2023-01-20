<?php

namespace App\Utils\OneTimeJobs;

require __DIR__.'/../../../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Text;

/**
 * Reparse all texts.
 *
 * Previously, Lute used to store terms as space-delimited things,
 * but now each term is saved as a zero-width-space-joined set of tokens,
 * and the sentences saved in sentences table also have zero-width tokens
 * included (to standardize searching for terms in the sentences later).
 *
 * The only way to be sure everything is parsed and associated correctly
 * is to reparse everything.
 */
class ReparseAll {
    public static function do_reparse($showecho = true) {
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

        $texts = $repo->findAll();
        $c = count($texts);
        $echo("Reparsing {$c} texts.\n");
        $n = 0;
        foreach ($texts as $t) {
            $n += 1;
            $echo("{$n} of {$c}\n");
            $repo->save($t, true);
        }
        $echo("Done.\n");
    }
}

?>