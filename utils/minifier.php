<?php
require __DIR__ . '/../vendor/autoload.php';
use MatthiasMullie\Minify;

function minify_css() 
{
    $cssFiles = array(
        'jquery-ui.css',
        'jquery.tagit.css',
        'styles.css',
        'styles-compact.css',
    );
    $missing = array_filter($cssFiles, fn($f) => !file_exists('src/css/' . $f));

    if (count($missing) > 0) {
        echo("\n\nERROR, MISSING FILES: " . implode(', ', $missing) . "\n\n");
        echo("Not minifying anything, please fix problem ...\n");
        die();
    }

    echo "Minifying CSS...\n";
    foreach ($cssFiles as $name) {
        $minifier = new Minify\CSS();
        $minifier->add('src/css/' . $name);
        $minifier->minify('public/css/' . $name);
    }
}

?>