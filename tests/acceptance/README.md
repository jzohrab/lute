# Acceptance tests

Using Panther.

To see the browser while tests are running:

```
PANTHER_NO_HEADLESS=true composer test tests/acceptance
```

# Tests to write:

```
- Languages
  - List languages
  - Create new lang
- Create text
  - from textbox
  - from file
- Import web page
- Texts
  - archive text
  - view archive
  - unarchive text
  - delete text
- Terms
  - list terms
  - search for terms
  - create new term from main form
  - bulk map parents from listing
- Reading
  - Update refreshes multiple terms
  - Update on one page updates other books
- Parent term mapping
  - export book file
  - export language file
  - import mapping file
- Create backup
- Version and software info

```