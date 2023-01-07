# Development

Install [composer](https://getcomposer.org/download/).

Then install dependencies:

`composer install --dev`

## Branches

This project uses [gitflow](https://www.gitkraken.com/learn/git/git-flow).

* **master**: the main branch, used for release.  It should only contain stable code.
* **develop**: the development branch.  I use this branch for my day-to-day Lute usage, so that I can be reasonably sure that the code is well-baked before it's merged into master.  All PRs should go into this branch, unless it's a prod hotfix.  When this branch passes CI and has been stable for a reasonable length of time, it's merged into master.
* other branches: features I'm working on.

## Tests

**Important!  You have to use the config file phpunit.xml.dist when running tests!**  So either specify that file, or use the composer test command:

```
./bin/phpunit -c phpunit.xml.dist tests/src/Repository/TextRepository_Test.php

composer test tests/src/Repository/TextRepository_Test.php
```

Most tests hit the database, and refuse to run unless the database name starts with 'test_'.  This prevents you from destroying real data!

The `.env.test` has a valid db name already, but if you're using an `.env.test.local`, then set the DB_DATABASE to `test_<whatever>`, and create the `test_<whatever>` db using a dump from your actual db, or just create a new one.  Then the tests will work.

Running phpunit with the `phpunit.xml.dist` file correctly uses the `env.test` file.

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
  dev:data:load            Abuse the testing system to load the dev db with some data.
  dev:data:clear           Abuse the testing system to wipe the dev db.
  dev:dumpserver           Start the dump server
  dev:find                 search specific parts of code using grep
  dev:nukecache            blow things away, b/c symfony likes to cache
  dev:psalm                Run psalm and start crying
 test <filename|blank>     Run tests
  test:group               Run tests with a given '@group xxxx' annotation
 todo
  todo:list                Show code todos
  todo:types               Show types of todos
```

* re dumpserver: ref https://symfony.com/doc/current/components/var_dumper.html
