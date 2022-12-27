# Installation

The setup for this project is much the same as [LWT](https://github.com/HugoFara/lwt):

* get an Apache server with PHP and MySQL
* create an ".env.local" file (and ".env.test.local" if you're running tests)

Unlike LWT, which just uses plain php files, Lute uses the [symfony](https://symfony.com/) framework, and so has more requirements:

* PHP version least 8.1
* If you're doing dev, you'll need [composer](https://getcomposer.org/download/) to install the dependencies

My personal Lute is running Apache/2.4.54, with PHP version 8.1.13.

## Set up your environment

This part can be tricky, so see the [installation readme](./installation/README.md).

Also included are some [actual apache config files](./installation/sample_apache_config_files/README.md) as I use them on my personal machine.  These are for reference only, but maybe they will help someone.

## Get the code

If you're a dev, you can "git clone" the project to your machine.

You can also get "lute_release.zip" from the appropriate GitHub release, and unpack it to a directory (e.g. to "Public" on mac, or in "htdocs" in MAMP).  The release zip file has all of the code and dependencies for Lute to run.

## .env, .env.test, .env.local, and .env.test.local

Lute uses "environment files" in the root folder for basic configuration.

The default file, `.env`, may be sufficient if your mysql setup matches the various DB_ values in that file.

If those values aren't good for you, then make a copy of `.env.local.example` named `.env.local`, and edit that file for your setup.

For developers, you can do the same with `.env.test`: copy `.env.test.local.example` to `.env.test.local` and edit that.

> Don't touch `.env` or `.env.test`; instead, make your own `...local` files and edit those.
