<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\Backup;
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals enabled
 */
final class Backup_Test extends DatabaseTestBase
{

    public function test_missing_keys_all_keys_present() {
        foreach (Backup::$reqkeys as $k) {
            $_ENV[$k] = $k . '_value';
        }
        $b = new Backup($_ENV);
        $this->assertTrue($b->config_keys_set(), "all keys present");
    }

    public function test_missing_keys() {
        foreach (Backup::$reqkeys as $k) {
            $this->assertFalse(array_key_exists($k, $_ENV), "shouldn't have key " . $k);
        }
        $b = new Backup($_ENV);
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), implode(', ', Backup::$reqkeys));
    }

    public function test_one_missing_key() {
        foreach (Backup::$reqkeys as $k) {
            $_ENV[$k] = $k . '_value';
        }
        $_ENV['BACKUP_DIR'] = null;

        $b = new Backup($_ENV);
        $this->assertFalse($b->config_keys_set(), "not all keys present");
        $this->assertEquals($b->missing_keys(), 'BACKUP_DIR');
    }

}
