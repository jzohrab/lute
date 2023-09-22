# LUTE - Learning Using Texts

[![CI master](https://github.com/jzohrab/lute/actions/workflows/symfony-ci.yml/badge.svg?branch=master)](https://github.com/jzohrab/lute/actions/workflows/symfony-ci.yml?query=branch%3Amaster)
[![Discord Server](https://badgen.net/badge/icon/discord?icon=discord&label)](https://discord.gg/CzFUQP5m8u)

This is a fork and ground-up rewrite of Learning with Texts, for learning foreign languages through reading.  You install Lute on your computer, import texts, and annotate terms.

Lute contains the core features you need for learning through reading:

* defining languages and dictionaries
* creating and editing texts
* creating terms and multi-word terms

In addition, Lute has features missing from other tools, which I consider absolutely essential:

* Defining "parent terms".  For example, for Spanish, _hablar_ (to speak) is the root form of _habló_ (he spoke).  In Lute, you can set a root form as the Parent Term, and the definition for that root form is available for all child terms.  Also, when you ask Lute for sample sentences using a term, it includes sentences for all related terms as well.  See [the wiki](https://github.com/jzohrab/lute/wiki/Parent-Terms) for examples.
* Adding word images.  This helps learning, and is more interesting and entertaining than adding reams of text.

I have been using Lute for learning Spanish, it's a super tool.

## Demo

A _very_ brief demo, showing the core feature: reading a doc, and creating a term:

[comment]: # (See docs/adding_readme_gif.md for notes)

![A wee demo](https://user-images.githubusercontent.com/1637133/210660839-b9aebebc-60c6-43fc-9f6d-daf2c448f825.gif)

_(fyi - The screenshot was edited for time and file size, so it looks like Lute is automatically filling in the term form -- it's not.)_

Lute has other features not shown. See the [YouTube introduction video](https://youtu.be/cjSqQTwUFCY) -- it's a bit out-of-date, but gives you an idea -- and check out the [Wiki](https://github.com/jzohrab/lute/wiki).

# Installation

The easiest way to get started with Lute is using Docker.  See [Running Lute using Docker](https://github.com/jzohrab/lute/wiki/Getting-Started) in the Lute Wiki.

If you can't use Docker, you can also [install everything manually](https://github.com/jzohrab/lute/wiki/Getting-started-without-Docker), which is how I run Lute myself on my Mac.

# Docs

Docs are in the [Wiki](https://github.com/jzohrab/lute/wiki).

You can also join [the Lute Discord Server](https://discord.gg/CzFUQP5m8u).

# Contribution

If you're a gearhead, like me:

* Read the [development notes](https://github.com/jzohrab/lute/wiki/Development)
* Fork this repo
* Run `composer install --dev` to install dependencies.  If running acceptance tests, run `vendor/bin/bdi detect drivers` to install Panther drivers
* Make your changes
* Run `composer test:full` to ensure everything passes.  This runs psalm, Doctrine ORM mapping checks, and unit and acceptance tests
* Open a PR to the `develop` branch

If you're a user: Lute is free :-) ... but if it makes your life better and you feel like saying thanks, I gladly accept <a href="https://www.buymeacoffee.com/jzohrab" target="_blank">coffee</a>.  I'll give thanks and will use the caffeine to implement features, or, better, recruit devs to grow the project.

# Unlicense

Under unlicense, view [UNLICENSE.md](UNLICENSE.md), and check out [http://unlicense.org/].

# Acknowledgements

Lute would never have existed without the original [Learning With Texts](https://sourceforge.net/projects/learning-with-texts) and [Hugo Fara's fork](https://github.com/hugofara), so **a big thanks to both of these projects.**
