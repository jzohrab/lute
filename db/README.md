# Database migrations

This folder contains simple db scripts and and migrations for db schema management for Lute.

The schema is managed following the ideas outlined at https://github.com/jzohrab/DbMigrator/blob/master/docs/managing_database_changes.md.

* All migrations are stored in the `migrations` folder, and are applied once only, in filename-sorted order.
* The main class `mysql_migrator.php` is lifted from https://github.com/jzohrab/php-migration.

## Changes must be manually applied.

The DB migrations are **not** applied automatically, as that is potentially a big change to make.

If you're developing or merge in changes, you may need to run the migrations.  If you're just continuing everyday usage and haven't upgraded/changed any code, you'll be fine.

## Usage

### Applying the migrations manually

```
$ composer db:migrate
```

### Creating new migration scripts

```
# one-time schema changes:
$ composer db:newscript <some_name_here>

# things that should always be applied (create triggers, etc):
$ composer db:newrepeat <some_name_here>
```

These migration scripts should be committed to the DB.