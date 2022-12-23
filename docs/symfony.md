# Moving to Symfony

Originally, I had hoped to restructure the code per the ideas in [on-structuring-php-projects](https://www.nikolaposa.in.rs/blog/2017/01/16/on-structuring-php-projects/), but then felt that this was just delaying the much-needed massive code rewrite.

I started to introduce a "no framework" restructure, per [the "no framework" tutorial](https://github.com/PatrickLouys/no-framework-tutorial), but after a few steps I felt that I was merely re-implementing the Symfony framework.

I looked at a few frameworks (laravel, yii), and based on gut feeling only, went with Symfony.

## Overview

Fundamentally, it appears to me that the LWT code really consists of a few core things:

* parsing texts into tokens, which users can then mark as "words" for known topics.
* the reading pane, where users interact with texts.

It appears that the data model for the above is decent: `texts`, `words`, `textitems2`, etc are decent structures (even if some of the table names are odd, for historical reasons).  There's no need to change much there.

The parsing and pane interaction have some hairy javascript, php, and sql, so those just need some more tests to ensure things are good.

Pretty much everything else is just "CRUD" (i.e., it's a simple database "Create-Retrieve-Update-Delete" app).  Most of that can be handled with simpler code, or generated code.

## Current state

MVP phase 1 completed late December 2022.