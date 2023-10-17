<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';


final class SettingsRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        DbHelpers::exec_sql("delete from settings where stkey='zztrash'");
    }

    public function test_save_and_retrieve() {  // V3-port: TODO
        $this->settings_repo->saveSetting('zztrash', 42);
        $v = $this->settings_repo->getSetting('zztrash');
        $this->assertEquals(intval($v), 42, 'got setting back');

        $this->settings_repo->saveSetting('zztrash', 99);
        $v = $this->settings_repo->getSetting('zztrash');
        $this->assertEquals(intval($v), 99, 'updated');
    }

    public function test_missing_key_value_is_null() {  // V3-port: TODO
        $v = $this->settings_repo->getSetting('zzmissing');
        $this->assertTrue($v == null, 'missing setting = null');
    }

    public function test_smoke_last_backup() {  // V3-port: TODO
        $v = $this->settings_repo->getLastBackupDatetime();
        $this->assertTrue($v == null, 'not set');

        $this->settings_repo->saveLastBackupDatetime(42);
        $v = $this->settings_repo->getLastBackupDatetime();
        $this->assertTrue($v == 42, 'set');

    }
}
