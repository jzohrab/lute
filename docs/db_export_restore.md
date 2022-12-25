# Exporting and restoring the database.

Lute doesn't have a UI for exporting and importing databases.  There are a few reasons for this:

* imports are a *big deal* -- it's too easy to write over things.
* With database migrations, it's also easy (aka bad) for users to restore non-compatible schema versions, and they'd have to run db migrations from the command line.
* In a real-world system, devs or DBAs would handle backups and restores.

In the meantime, here are some notes about using `mysqldump`:

## Exporting a backup file:

```
mysqldump --complete-insert --quote-names --skip-triggers --user=root --password=root dbname > dbexport.sql
```

## Creating a new database using the exported file.

```
mysqladmin -u root -proot drop somedbname

mysqladmin -u root -proot create somedbname

mysql -u root -proot somedbname < dbexport.sql
```

**After you've imported the new db, change `.env.local` and `env.test.local` to use the new db.**

Then verify the db, and run any outstanding migrations:

```
composer db:which

composer db:migrate
```