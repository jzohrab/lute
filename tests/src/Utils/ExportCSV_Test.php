<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\ExportCSV;

// Smoke test only.
final class ExportCSV_Test extends DatabaseTestBase
{

    public function test_smoke_test() {
        ExportCSV::doExport();
        $this->assertEquals(1,1,"dummy");
    }

}
