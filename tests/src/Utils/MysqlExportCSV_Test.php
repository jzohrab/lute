<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\MysqlExportCSV;

// Smoke test only.
final class MysqlExportCSV_Test extends DatabaseTestBase
{

    public function test_smoke_test() {
        MysqlExportCSV::doExport();
        $this->assertEquals(1,1,"dummy");
    }

}
