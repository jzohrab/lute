<?php
use Symfony\Component\Dotenv\Dotenv;
use App\Utils\MysqlHelper;

require __DIR__ . '/../../vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/../../.env');

echo "\nSetting up {$_ENV['DB_DATABASE']}\n\n";
[ $messages, $error ] = MysqlHelper::doSetup();

echo "Done.\n";

if ($error != null) {
    echo "ERROR: \n" . $error . "\n";
    throw new \Exception($error);
}

echo "Messages:\n";
foreach ($messages as $m) {
    echo $m . "\n";
}

?>