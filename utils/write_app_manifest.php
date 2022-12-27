<?php

require __DIR__ . '/../src/Utils/AppManifest.php';
App\Utils\AppManifest::write();
$f = App\Utils\AppManifest::manifestPath();
echo "Wrote $f \n";