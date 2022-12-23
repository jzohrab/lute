<?php
/**
 * Migrates the LWT db defined in connect.inc.php.
 */
require_once __DIR__ . '/lib/migration_helper.php';
MigrationHelper::apply_migrations(true);
?>