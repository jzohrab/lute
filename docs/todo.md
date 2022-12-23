# MVP Phase 1 is done.

The MVP phase was to get the minimum set of features implemented under the new Symfony framework:

* define languages
* create a text
* parsing and rendering
* right pane word definition pop-up
* create terms and multiword terms
* remove all legacy code
* setting statuses, and bulk status updates.

A bunch of LWT features were [removed for the MVP](lwt_features_that_were_removed.md).

# MVP Phase 2

A rough list of big-ish features to add once MVP1 is done:

* statistics
* import terms
* help/info
* manage term tags
* manage text tags
* import a long text file

# Small-ish features to add at any point

* Command-line job to fix bad multiword expressions.  See devscripts/verify_mwords.sql for a starting point.
* Check text length constraint - 65K too long.
* Add repeatable migrations to db migrator
* Move trigger creation to repeatable migration
* Fix docs for exporting a DB backup, skip triggers (trigger in dumpfile was causing mysql to fail on import)
* Language "reparse all texts" on language change - or just reparse on open

# Post-MVP

* anki export
* bulk translation (?)
* archive language.  (Deleting would lose all texts, so just archive the language and all its texts, and have option to re-activate).
* Playing media in /public/media or from other sources.


# Why scrapping

* remove all existing anki testing code

