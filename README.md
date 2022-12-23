# LUTE - Learning Using Texts

This is a fork and ground-up rewrite of [Hugo Fara's fork](https://github.com/hugofara) of the original Learning with Texts project on [Sourceforge](https://sourceforge.net/projects/learning-with-texts).

> Lute would never have existed without the original Learning With Texts and Hugo Fara's subsequent efforts, so **a big thanks to both of these projects.**

See [the docs](./docs/README.md) for notes about this project, why it was forked, to-dos, etc.

> TODO:docs - add a screencast gif.


## Should I use Lute, LWT, or Hugo's fork?

Sensible question, and it's up to you.

* The [original LWT project](https://sourceforge.net/projects/learning-with-texts/) is, as far as I can tell, still *massively popular.*  Even if the code is old, and it might have bugs or be slow, it has the advantage of being tried-and-tested, and you might find people who can help you with issues.
* [Hugo Fara's fork](https://github.com/hugofara) has taken the original project and refined the code, but has kept the same features and overall design/architecture.

I am currently using Lute to read texts in Spanish, and it works great.  It should work very well for other left-to-right languages like French, German, English, Italian, etc.

### So why even consider Lute?

Top answer: For me, it feels faster and lighter than LWT, and it has just enough features to be useful.  The UI's a bit leaner, if that matters to you.

**Note:** if you are currently using LWT, you should be able to export a copy of your database, and start using it with Lute, after setting up the necessary software.  You'll just need to migrate your old LWT-style database to the new Lute database (see [database migrations](./db/README.md)).

More geeky reasons:

I believe that Lute is a useful contribution to the language-learning-software landscape.  It implements the core features of LWT in a fraction of the size of the LWT codebase, using modern PHP tools, and with automated tests for stability.

This might not mean much to regular peeps who just want to learn languages. :-)  At least, at the moment ...

But if you are into software, like the idea of LWT, and want to contribute to an open-source project, I believe that Lute is a compelling place to start.

### Why might you **not** use Lute?

* Currently, Lute is an MVP, and many LWT features were [removed for the MVP](lwt_features_that_were_removed.md).  If that's a dealbreaker, Lute's not for you.
* Lute currently doesn't support languages like Japanese.  I didn't have good test data for that, and so couldn't implement it, or port the old LWT code over for it.
* Like LWT, Lute is currently the work of just one guy ... me.  And it has a user base of one ... also me.  I'll answer questions and fix issues as I can, but it's _currently_ just me working on this project.

### ... and why did I write it?

* I started using LWT, but wanted a single new feature: adding "parent terms" to terms.  To me, it doesn't make sense to think of a conjugated form of a verb ("I _speak_", "yo _hablo_") as a separate thing from the root form ("to speak", "_hablar_").  I added the feature, but it was very tough.  Lute has that feature.
* There were some bugs in LWT that were impossible to track down.  For example, when adding multi-term expressions, LWT would sometimes find them, and sometimes miss them.  Lute corrects those issues, and adds a series of automated tests to help track down those problems.
* As a former dev, there were some things about LWT that I simply couldn't get behind: lack of automated testing, tough database management, tough architecture, etc.

In summary, I felt that LWT was **an extremely important idea**, but I felt that **its implementation made it hard to fix problems, and created barriers for its improvement**.

Even if Lute doesn't become "the new LWT" that I hope it can be, perhaps it will be useful as a reference implementation.


<hr />

# Installation, usage, etc.

> TODO:docs - the docs for installation need work, and perhaps some things can be simplified, such as removing vhosts.

This project does not have "GitHub releases" yet, so just clone the repo to your machine, or get the latest [zipfile from GitHub](https://github.com/jzohrab/lute/archive/refs/heads/master.zip), and unpack it in a directory in your "Public" folder.

The setup for this project is much the same as [LWT](https://github.com/HugoFara/lwt):

* get an Apache server with PHP and MySQL
* Enable "load local infile" for MySQL server
* create a "connect.inc.php"

Unlike LWT, which just uses plain php files, Lute uses the [symfony](https://symfony.com/) framework, and so has more requirements:

* You'll need [composer](https://getcomposer.org/download/) to install the dependencies
* PHP version least 8.1
* Apache: enable virtual hosts and URL rewrites.
* Apache: create a Virtual Host to redirect requests to the Lute "front controller"
* Edit your `etc/hosts` to use the new Lute URL in your browser

My personal Lute is running Apache/2.4.54, with PHP version 8.1.13.

### PHP version

For Mac installing later versions of PHP on MAMP, see [this link](https://gist.github.com/codeadamca/09efb674f54172cbee887f04f700fe7c).

### Apache virtual host

Ref https://davescripts.com/set-up-a-virtual-host-on-mamp-on-mac-os-x

For me on my mac, the virtual hosts file was at `/usr/local/etc/httpd/extra/httpd-vhosts.conf`, and I added the following, specifying my particular path:

```
<VirtualHost *:8080>
    DocumentRoot "/Users/jeff/Public/lute/public"
    ServerName lute.local
    <Directory "/Users/jeff/Public/lute/public">
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

After saving the file, restart Apache, and check the configuration:

```
sudo apachectl restart    # Restart :-)
apachectl -S              # Check your vhost config:
```

### MySQL load local infile

ref https://dba.stackexchange.com/questions/48751/enabling-load-data-local-infile-in-mysql

The app and many tests require 'load local infile' to be set to On, so you'll need to set that in your php.ini.  For me, for example, the file I changed was at `/usr/local/etc/php/8.1/php.ini`.


### Edit your /etc/hosts

Note that the `ServerName` above is `lute.local` ... so then you need to edit your `/etc/hosts` file so that you can enter "http://lute.local:8080/" in your browser.

For a mac, do the following:

```
sudo vi /etc/hosts
```

Add the line

```
127.0.0.1       lute.local
```

Then in a browser window, go to http://lute.local:8080/ - if it pops up, your basic mappings are fine.

### connect.inc.php

Copy the file `connect.inc.php.example` to `connect.inc.php`, and specify your values for the variables (server, userid, password, db name).


# Development

Install [composer](https://getcomposer.org/download/).

Then install dependencies:

`composer install --dev`

## Branches

* **master**: the main branch I use for Lute.
* other branches: features I'm working on.

## Tests

Most tests hit the database, and refuse to run unless the database name starts with 'test_'.  This prevents you from destroying real data!

In your connect.inc.php, change the `$dbname` to `test_<whatever>`, and create the `test_<whatever>` db using a dump from your actual db, or just create a new one.  Then the tests will work.

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


## Contribution

* Fork this repo
* Run `composer install --dev` to install dependencies
* Make and test your changes
* Open a PR


## Unlicense
Under unlicense, view [UNLICENSE.md](UNLICENSE.md), please look at [http://unlicense.org/].

## Lute is free :-)

... but if it makes your life better and you feel like saying thanks, I do drink <a href="https://www.buymeacoffee.com/jzohrab" target="_blank">coffee.</a>  I'll use the caffiene to implement features, or recruit devs to grow the project.