# Changelog

## 2023-07-15 v2.0.8

Feature changes:

* Change hotkeys to also work on currently hovered word (issue [46](https://github.com/jzohrab/lute/issues/46))
* Add "shift-drag" copy-to-clipboard
* Update tutorial to explain new hotkey handling (included in [the wiki](https://github.com/jzohrab/lute/wiki/Tutorial))

Back end changes:

* Add hacky lute.js cachebusting, so that browsers serve correct Javascript on Lute update!
* Hotkey acceptance tests


## 2023-07-10 v2.0.7

Feature changes:

* Better handling for overlapping Terms (issue [52](https://github.com/jzohrab/lute/issues/52), and [wiki docs](https://github.com/jzohrab/lute/wiki/Overlapping-Terms))
* Add hotkeys `c` and `C` to copy selected word sentence and paragraph (issue [49](https://github.com/jzohrab/lute/issues/49))
* Bug fixes for Term Import: handle unicode spaces and hidden file characters (issues [50](https://github.com/jzohrab/lute/issues/50) and [51](https://github.com/jzohrab/lute/issues/51))

## 2023-07-04 v2.0.6

Feature changes:

* Use suggestions/placeholders for term form, not labels
* add bulk term import (issue [46](https://github.com/jzohrab/lute/issues/46))


## 2023-06-26 v2.0.5

Feature changes:

* Allow editing of book title and tags (issue [37](https://github.com/jzohrab/lute/issues/37))
* Handle parent mapping duplicates (issue [40](https://github.com/jzohrab/lute/issues/40))
* Add Greek language and sample text

Back end changes:

* Update release scripts and process
* Back end changes for supporting book edits (Remove BookBinder, texts.TxTitle)
* Fix FK integrity and cascade deletes (issue [38](https://github.com/jzohrab/lute/issues/38))


## 2023-06-18 v2.0.4

Feature changes:

* Add term filtering, which lets you do things like "show me terms in the past 3 days".  See the [wiki](https://github.com/jzohrab/lute/wiki/Term-Listing).  I find this useful after reading to quickly review new things I've added.
* Add a "Term Tag" listing page, to see tags and the jump to Terms assigned to them.
* Add Language deletion.

Back end changes:

* More acceptance/browser-level tests for project stability (book creation, tag listing)


## 2023-06-02 v2.0.3

This release adds a few small features, and gets rid of some old code and database fields.  Per the notes on the wiki about [upgrading Lute](https://github.com/jzohrab/lute/wiki/Upgrading-Lute), don't forget to backup your database prior to upgrading, _just in case_.  (I've run the upgrade several times on my machine.)

Feature changes:

* Allow period in terms (ref [issue 28](https://github.com/jzohrab/lute/issues/28))
* Only show sentences for texts I've actually read (ref [issue 34](https://github.com/jzohrab/lute/issues/34)).  The navigation buttons on text page footers set the "read date" for texts
* Show "Sentences" link even when defining new term, so that users can see where a new term has been used in the past.  Useful when defining new multi-word terms that they may have encountered already.

Back end:

* Add stack traces to error screens
* Add initial set of browser-level acceptance tests using Panther
* Add page number to page title
* Remove obsolete words table fields


## 2023-05-15 v2.0.2

Feature changes:

* Add manual bulk mapping of parents to child terms.  Useful when importing new books.  See [the wiki](https://github.com/jzohrab/lute/wiki/Bulk-Mapping-Parent-Terms) for notes

Back end:

* Add browser-level acceptance tests using Panther.  Currently somewhat flaky due to timing issues.
* Remove dead code
* Add test coverage
* Fix license
* Fix classical chinese parse error.

## 2023-04-24 v2.0.1

Feature changes:

* List books on Home page
* Add automatic Japanese readings for terms
* Enable basic HTTP auth for simple security (optional)
* Add rolling backup
* Improve term Sentences listing
* Less wide tooltip for terms
* Update tutorial text

Back end changes:

* Full parse on initial book save, improves stats performance
* Add composer dev:start and stop for convenience
* Change Dictionary to TermService.
* Fix issue 29: remove double spaces in text.
* Sanity check tests for term deletion.


## 2023-04-17 v2.0.0

Big changes:  Moving from MySQL to Sqlite, and adding Docker support.

Users of v1 will need to export CSV data from v1.  See the wiki: https://github.com/jzohrab/lute/wiki/Migrating-from-v1-to-v2

Big user-facing changes

* Change from Mysql to Sqlite, including .env file configuration
* Move all user specific files (db, user images) to /data subdirectory
* Add CSV import for v1 users
* Add Docker support

Smaller user-facing changes

* Remove dashed underline for ignored words, no need for clutter.
* Read custom styles from .env file.
* Add convenience links on last page of book reading.
* Demo data is automatically loaded in new installation

Back end changes:

* Remove all mysql-related code
* Revamp code as needed for Sqlite-specific sql


## 2023-04-15 v1.3 (end of life of Lute V1)

Feature changes:

* Add CSV export
* Open sentence link in new tab.

Back end:

* Make Mysql-specific code more obvious
* Rename classes
* Db cleanup: change engine, add fks, integrity.

## 2023-04-03 v1.2.1

Feature changes:

* Show tooltip if there any extra info defined for term (issue https://github.com/jzohrab/lute/issues/18)
* Bugfix for parsing "EE.UU." (issue https://github.com/jzohrab/lute/issues/16)
* Import a web page into new book (see https://github.com/jzohrab/lute/wiki/Importing-Web-Pages)
* Reload term listing on parent change.
* Remove some cruft from the reading page: only show title at start of book, nav links on page footer.

Back-end changes:

* Change "romance language parser" to better "space delimited parser"
* Remove some dead code
* Only show language dropdown if mult langs exist


## 2023-03-27 v1.2.0

Feature changes:

* Add automated backups (ref notes in https://github.com/jzohrab/lute/wiki/Backup).  You will be prompted to make some changes to your `.env.local` file, adding some settings as described in the wiki.

Back end (some very big changes, which simplified many sections of the code):

The changes forced some API changes which may break if your front-end Javascript is cached (in the term form of the reading pane).  Update the page a few times, and clear your javascript cache, and it should sort itself out. :-)

* Use texttokens for rendering and updating
* Remove textitems2 table and references
* Remove TextItemRepository


## 2023-03-21 v1.1.7

Feature changes:

* add classical chinese parser and demo
* return to same term listing page on term save

Back end:

* misc fixes (language to texts link, last word parse)
* use model for reference lookups
* better test abstractions

## 2023-03-17 v1.1.6

* add "books" (i.e. multi-page texts) and book stats
* add long text rebinding into a book
* add text file upload for new book creation
* pagination moves through pages in book

Back end changes:

* lazy parsing of book pages
* remove unused code
* new domain classes for future parsing work
* add texttokens table

## 2023-03-13 v1.1.5

* Fix issue 10: Case-insensitive search for mword terms.
* Avoid 'dynamic property deprecated' php8.2 noise.

## 2023-03-02 v1.1.4

* Fix issue 6: multiword parent updates text items.

### Back end fixes

* Improve term mapping: only map terms that exist in text.
* Remove unused code, refactor some tests.


## 2023-02-24 v1.1.3

* set existing parent description and image on save if not already set
* add bulk setting of parent from term listing page
* add parent to term listing
* include images in term listing
* improve text lookup for setting parents: existing parents sort to top of list
* more tests

## 2023-02-13 v1.1.2

* Don't search translation field in term list.
* Allow "^" and "$" special characters in term and text listing searches.

### Back end changes

* Fix php 8.2 deprecation warnings
* JS bugfix, replaceAll for strings.
* Speed up text listings, only recalc stats if required.


## 2023-01-21 v1.1.1

* Fix for terms with spaces (use parser for terms)
* Increase scope of archive search when looking for sentences

### Back end changes

* Change parser to use parsed tokens

## 2023-01-20 v1.1.0

### Major changes

* Terms and sentences are now concatenated with a zero-width space.  **Users of earlier versions will need to update the `words` and `sentences` table.**  See https://github.com/jzohrab/lute/wiki/Upgrading-from-v1.0.3-or-earlier for notes.
* Japanese parser added
* Parsing type is now specified as part of the Language setup
* Sentences containing a term, its parents and siblings can be listed
* Allow option to hide "Romanization" field for languages that really don't need it

### Minor changes

* Store image in parent if new parent.
* Documentation moved to wiki.
* Local authentication disabled for speed
* Fix image click updates on main term page
* Images pre-loaded in reading page for popup speed
* "Clear cache" link added to main page and error page, useful during upgrades

### Back end changes

* Cleanup test code
* Strengthen ReadingFacade


## 2023-01-08 - v1.0.3

* saving user images in `public/userimages`, linking directly to terms.  (If you have been using v1.0.2 actively since its release a few days ago, you'll need to [clean up your data](https://github.com/jzohrab/lute/wiki/Migrating-to-userimages) ... sorry!)
* cleaning up the term hover-over tooltip popup

### back end work:

* add TermDTO object to simplify term form processing
* github CI

## 2023-01-04 - v1.0.2

* Save selected bing image to public/media/images.
* Include local files in term hovers.

## 2023-01-03 - v1.0.1

* Notes for installing on XAMPP.
* Test coverage for db setup.
* Allow blank database password in .env.
* Fix text tags available tags source.
* Note on missing language support.

## 2022-12-31 - v1.0.0

Finished minimum viable product (MVP):

* create texts
* define languages and terms
* demo and tutorial
* back-end stuff (migrations, tests, etc.)

## 2022-12

* Start of MVP, forked off of https://github.com/HugoFara/lwt.
