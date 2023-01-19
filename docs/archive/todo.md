# Current state

Dec 2022: MVP phase one is done (see "MVP Phase 1" below).  A bunch of LWT features were [removed for the MVP](lwt_features_that_were_removed.md).

# Future

## MVP Phase 2

A rough list of big-ish features to add once MVP1 is done:

* import a long text file.  Possible implementation: import the long text file as a single Text entity, and then have a "split text" function that splits the `sentences` and `textitem2` entries into new Text entities.
* statistics
* import terms
* manage term tags
* manage text tags
* show sentences for terms.

## Small-ish features to add at any point

* Command-line job to fix bad multiword expressions.  See devscripts/verify_mwords.sql for a starting point.
* Check text length constraint - 65K too long.
* Fix docs for exporting a DB backup, skip triggers (trigger in dumpfile was causing mysql to fail on import)
* Language "reparse all texts" on language change - or just reparse on open

## Post-MVP

* anki export
* bulk translation (?)
* archive language.  (Deleting would lose all texts, so just archive the language and all its texts, and have option to re-activate).
* Playing media in /public/media or from other sources.

## Bigger concerns

Thoughts to shore up project technical structure and stability.

This section is a bunch of big ideas, and if Lute gets popular they may be worth exploring further.  Even though Lute is sort of a "toy project", it may grow, and some things may be useful to consider.

### Add Behat/BDD testing

I've written enough tests in Lute to ensure it works for my current cases, but if more stuff is added (e.g Japanese), more (and easier) testing would be good.

I'd say use Behat: https://docs.behat.org/en/latest/

The current code in tests/src/Domain/ReadingFacade_Test.php (e.g. `test_update_multiword_textitem_status_replaces_correct_item()`) may provide some of the wiring for BDD tests.

### Clarify architecture?

I'm not sure about this one.  I'm a fan of "Domain Driven Design", but I don't think Lute's core concept is tricky enough to merit a full DDD approach.  This may come back to haunt me later.

On the other hand, there are a few `Domain` half-baked ideas that are definitely good conceptually, but feel architecturally unclear, and are scattered in a few places:

* `Domain/ReadingFacade.php` - is basically a service layer for reading
* `Repository/ReadingRepository.php` - used by the `ReadingFacade`
* `Domain/RomanceLanguageParser.php` - for parsing text
* `Repository/TextItemRepository.php` - links `textitems2` and `words` records

It could make sense to restructure the files and directories so that 

# Past/Complete

## MVP Phase 1

The MVP phase was to get the minimum set of features implemented under the new Symfony framework:

* define languages
* create a text
* parsing and rendering
* right pane word definition pop-up
* create terms and multiword terms
* remove all legacy code
* setting statuses, and bulk status updates.
