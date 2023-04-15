# Baseline dbs

The dbs are called *.sqlite, and not *.db, because the
release/packaging script ignores all *.db files during package.

## baseline.sqlite

This is the empty database used by the unit tests.  It contains baseline schema only.

## demo.sqlite

This is created by `composer db:create:demo`.  It is the baseline.sqlite with all migrations applied, _and_ all of the demo data loaded.  This db is copied to the user's lute.db file when a new db is created at runtime, so the user gets a pre-populated demo.