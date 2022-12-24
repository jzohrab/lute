# Development

Install [composer](https://getcomposer.org/download/).

Then install dependencies:

`composer install --dev`

## Branches

* **master**: the main branch I use for Lute.
* other branches: features I'm working on.

## Tests

Most tests hit the database, and refuse to run unless the database name starts with 'test_'.  This prevents you from destroying real data!

The `.env.test` has a good db name already.  If you're using an `.env.test.local`, then set the DB_DATABASE to `test_<whatever>`, and create the `test_<whatever>` db using a dump from your actual db, or just create a new one.  Then the tests will work.

**You have to use the config file phpunit.xml.dist when running tests!**  So either specify that file, or use the composer test command:

```
./bin/phpunit -c phpunit.xml.dist tests/src/Repository/TextRepository_Test.php

composer test tests/src/Repository/TextRepository_Test.php
```

Examples:

```
# Run everything
composer test tests

# Single file
composer test tests/src/Repository/TextRepository_Test.php

# Tests marked with '@group xxx'
composer test:group xxx
```

## Useful composer commands during dev

(from `composer list`):

```
 db
  db:migrate               Run db migrations.
  db:newrepeat             Make a new repeatable db migration script (for triggers, etc)
  db:newscript             Make a new db migration script
  db:which                 What db connecting to
 dev
  dev:class                Show public interface methods of class
  dev:data                 Abuse the testing system to load the dev db with some data.
  dev:dumpserver           Start the dump server
  dev:find                 search specific parts of code using grep
  dev:minify               Regenerate minified CSS
  dev:nukecache            blow things away, b/c symfony likes to cache
  dev:psalm                Run psalm and start crying
 test <filename|blank>     Run tests
  test:group               Run tests with a given '@group xxxx' annotation
 todo
  todo:list                Show code todos
  todo:types               Show types of todos
```

* re dumpserver: ref https://symfony.com/doc/current/components/var_dumper.html
