<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\Backup;
use PHPUnit\Framework\TestCase;

/**
 * @backupGlobals enabled
 */
final class Backup_Test extends DatabaseTestBase
{

    public function test_s() {
        $this->assertEquals(1,1);
    }

    public function test_missing_keys_all_keys_present() {
        $keys = [
            'BACKUP_MYSQLDUMP_COMMAND',
            'BACKUP_DIR',
            'BACKUP_AUTO',
            'BACKUP_WARN'
        ];
        foreach ($keys as $k) {
            $_ENV[$k] = $k . '_value';
        }

        $b = new Backup();
        $this->assertFalse($b->is_missing_keys(), "all keys present");
    }
    
    /*
    public function test_smoke_can_get_params() {
        $keys = [ 'DB_HOSTNAME',
                  'DB_USER',
                  'DB_PASSWORD',
                  'DB_DATABASE' ];
        foreach ($keys as $k) {
            $_ENV[$k] = $k . '_value';
        }
        $arr = Connection::getParams();
        $expected = [
            'DB_HOSTNAME_value',
            'DB_USER_value',
            'DB_PASSWORD_value',
            'DB_DATABASE_value'
        ];
        $this->assertEquals($expected, $arr);
    }

    // XAMPP defaults to blank password.
    public function test_password_can_be_blank() {
        $keys = [ 'DB_HOSTNAME',
                  'DB_USER',
                  'DB_DATABASE' ];
        foreach ($keys as $k) {
            $_ENV[$k] = $k . '_value';
        }
        $_ENV['DB_PASSWORD'] = '';
        $arr = Connection::getParams();
        $expected = [
            'DB_HOSTNAME_value',
            'DB_USER_value',
            '',
            'DB_DATABASE_value'
        ];
        $this->assertEquals($expected, $arr);
    }

    public function test_other_keys_required() {
        $keys = [ 'DB_HOSTNAME',
                  'DB_USER',
                  'DB_PASSWORD',
                  'DB_DATABASE' ];
        foreach ($keys as $k) {
            $_ENV[$k] = '';
        }
        $this->expectException(\Exception::class);
        $arr = Connection::getParams();
    }
    */
}
