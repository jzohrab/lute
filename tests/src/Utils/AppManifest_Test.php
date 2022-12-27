<?php declare(strict_types=1);

use App\Utils\AppManifest;
use PHPUnit\Framework\TestCase;

/** Smoke tests only. */
final class AppManifest_Test extends TestCase
{
    public function test_missing_file_returns_empty() {
        if (file_exists(AppManifest::manifestPath()))
            unlink(AppManifest::manifestPath());
        $hsh = AppManifest::read();
        foreach (['commit', 'tag', 'release_date'] as $key) {
            $this->assertTrue($hsh[$key] == null, "$key = null");
        }
    }

    public function test_can_write_and_read() {
        if (file_exists(AppManifest::manifestPath()))
            unlink(AppManifest::manifestPath());
        AppManifest::write();
        $hsh = AppManifest::read();
        // dump($hsh);
        foreach (['commit', 'tag', 'release_date'] as $key) {
            // echo "'" . $hsh[$key] . "'\n";
            $exists = array_key_exists($key, $hsh);
            $this->assertTrue($exists, "have $key");
        }
    }
}
