# Database migrations

This folder contains simple db scripts and and migrations for db schema management for Lute.  The schema is managed following the ideas outlined at https://github.com/jzohrab/DbMigrator/blob/master/docs/managing_database_changes.md:

* Baseline schema and reference data are in `baseline`.
* All one-time migrations are stored in the `migrations` folder, and are applied once only, in filename-sorted order.
* All repeatable migrations are stored in the `migrations_repeatable` folder, and are applied every single migration run.
* See "code migrations" below for special PHP executors
* The main class `mysql_migrator.php` was lifted from https://github.com/jzohrab/php-migration, and then modified.

The front controller `index.php` calls `src/Utils/MigrationHelper.php` to apply changes automatically for users.

# Development

If you're developing and create migrations, you might want to create new scripts or run migrations manually.

## Applying the migrations manually


```
$ composer db:migrate
```

## Creating new migration scripts

```
# one-time schema changes:
$ composer db:newscript <some_name_here>

# things that should always be applied (create triggers, etc):
$ composer db:newrepeat <some_name_here>
```

These migration scripts should be committed to the repo.

## Code migrations

Sometimes, database migrations, or even code changes, require a
one-time job to be run.  Since that's a hassle for users, the
DbMigrator class can call regular PHP scripts during migrations.

Create a regular PHP script in the migrations folder, and it gets called.

See the existing .php script(s) for an example.
