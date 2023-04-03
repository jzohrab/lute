# Changelog

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
