<?php declare(strict_types=1);

require_once __DIR__ . '/../../db_helpers.php';
require_once __DIR__ . '/../../DatabaseTestBase.php';


final class SettingsRepository_Test extends DatabaseTestBase
{

    public function childSetUp() {
        DbHelpers::exec_sql("delete from settings where stkey='zztrash'");
    }

    public function test_save_and_retrieve() {
        $this->settings_repo->saveSetting('zztrash', 42);
        $v = $this->settings_repo->getSetting('zztrash');
        $this->assertEquals(intval($v), 42, 'got setting back');

        $this->settings_repo->saveSetting('zztrash', 99);
        $v = $this->settings_repo->getSetting('zztrash');
        $this->assertEquals(intval($v), 99, 'updated');
    }

}
