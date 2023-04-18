<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;

class SettingsRepository
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    // Very hacky setting saver.
    public function saveSetting($key, $value) {
        // sql injection is fun.
        $sql = "insert or ignore into settings (StKey, StValue) values ('{$key}', '$value')";
        $conn = $this->manager->getConnection();
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();

        $sql = "update settings set StValue = '$value' where StKey like '{$key}'";
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();
    }

    public function getSetting($key) {
        $sql = "select StValue from settings where StKey like '{$key}'";
        $conn = $this->manager->getConnection();
        $stmt = $conn->prepare($sql);
        $ret = $stmt->executeQuery()->fetchNumeric();
        if (!$ret)
            return null;
        return $ret[0];
    }

    public function saveCurrentTextID(int $textid) {
        $this->saveSetting('currenttext', $textid);
    }

    public function getCurrentTextID(): ?int {
        $v = $this->getSetting('currenttext');
        if ($v == null)
            return null;
        return intval($v);;
    }

    public function saveLastBackupDatetime(int $last) {
        $this->saveSetting('lastbackup', $last);
    }

    public function getLastBackupDatetime(): ?int {
        $v = $this->getSetting('lastbackup');
        if ($v == null)
            return null;
        return intval($v);
    }

}
