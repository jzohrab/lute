# Changelog

## 2023-01-21 v1.1.1

* Fix for terms with spaces (use parser for terms)
* Increase scope of archive search when looking for sentences

## Back end changes

* Change parser to use parsed tokens

## 2023-01-20 v1.1.0

## Major changes

* Terms and sentences are now concatenated with a zero-width space.  **Users of earlier versions will need to update the `words` and `sentences` table.**  See https://github.com/jzohrab/lute/wiki/Upgrading-from-v1.0.3-or-earlier for notes.
* Japanese parser added
* Parsing type is now specified as part of the Language setup
* Sentences containing a term, its parents and siblings can be listed
* Allow option to hide "Romanization" field for languages that really don't need it

## Minor changes

* Store image in parent if new parent.
* Documentation moved to wiki.
* Local authentication disabled for speed
* Fix image click updates on main term page
* Images pre-loaded in reading page for popup speed
* "Clear cache" link added to main page and error page, useful during upgrades

## Back end changes

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
