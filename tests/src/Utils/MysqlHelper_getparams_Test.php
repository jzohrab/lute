<?php declare(strict_types=1);

use App\Utils\MysqlHelper;
use PHPUnit\Framework\TestCase;

// Smoke tests only.
/**
 * @backupGlobals enabled
 */
final class MysqlHelper_getparams_Test extends TestCase
{

    public function test_smoke_can_get_params() {
        $keys = [ 'DB_HOSTNAME',
                  'DB_USER',
                  'DB_PASSWORD',
                  'DB_DATABASE' ];
        foreach ($keys as $k) {
            $_ENV[$k] = $k . '_value';
        }
        $arr = MysqlHelper::getParams();
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
        $arr = MysqlHelper::getParams();
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
        $arr = MysqlHelper::getParams();
    }
}
