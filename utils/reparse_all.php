<?php

/**
 * Reparse everything.
 */

require __DIR__ . '/../src/Utils/OneTimeJobs/ReparseAll.php';

App\Utils\OneTimeJobs\ReparseAll::do_reparse(true);