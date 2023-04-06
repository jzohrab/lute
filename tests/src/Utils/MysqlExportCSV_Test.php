<?php declare(strict_types=1);

require_once __DIR__ . '/../../DatabaseTestBase.php';

use App\Utils\MysqlExportCSV;

// Smoke test only.
final class MysqlExportCSV_Test extends DatabaseTestBase
{

    public function test_smoke_test() {
        if (str_contains($_ENV['DATABASE_URL'], 'sqlite'))
            $this->markTestSkipped('Not doing export for sqlite database ... have to re-architect this.');
        MysqlExportCSV::doExport();
        $this->assertEquals(1,1,"dummy");
    }

}
