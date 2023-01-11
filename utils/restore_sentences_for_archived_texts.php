<?php

require __DIR__ . '/../src/Utils/OneTimeJobs/RestoreArchivedTextSentences.php';

App\Utils\OneTimeJobs\RestoreArchivedTextSentences::do_restore(true);