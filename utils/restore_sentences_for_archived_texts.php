<?php

/**
 * Restore sentences for archived texts.
 *
 * Previously, archived texts used to delete their sentences and
 * textitem2 entries, but that makes looking for references
 * (Dictionary->findReferences()) less useful.  It's minimal space, so
 * now sentences are left alone.
 */

require __DIR__ . '/../src/Utils/OneTimeJobs/RestoreArchivedTextSentences.php';

App\Utils\OneTimeJobs\RestoreArchivedTextSentences::do_restore(true);