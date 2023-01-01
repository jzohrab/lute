## LWT features that were scrapped, and reasons for it.

All of the LWT code was scrapped, as it was not manageable.  (That's my judgement as a former professional software guy.)

These features *might* be re-introduced after the MVP is done.

| Feature | Notes |
| --- | --- |
| Japanese / Chinese, and right-to-left languages | For Japanese, parsing using MECAB isn't implemented, simply because I didn't have accurate test cases or expertise in its uses with LWT.  I couldn't port the existing LWT code over for these languages, because it was too complex and untested.  With good use cases -- sample stories and expected behaviour -- this should be implementable.  Same goes for right-to-left languages like Hebrew. |
| anki-like testing | The current testing code isn't the best.  It assumes that it should just potentially test everything.  I should be able to select the terms I want to test, especially parent terms that implicitly include many child sentences.  Needs a big rearchitecture. |
| multi-word edit screen | i.e. `/edit_words.php` - to be replaced by a datatables-type view. |
| bulk translation | A good idea, but the code was pretty rough. |
| Docker | This is probably a very good thing to look into, but users would have to have docker on their systems, which might not be common.  Who knows?  And, of course, the docker-to-database issue, which was a big deal a few years ago, might be tricky. |
| theming | Symfony has theming options, should look into that. |
| rss feeds | The current code was brittle for me, at least, it didn't work.  My guess is that it can be simplified. |
| text annotations | i.e., texts.TxAnnotatedText field, and all "Improved annotation" such as `/print_impr_text.php?text=1`.  The data is currently stored in a non-relational way in the database, and doesn't need to be. |
| db import and export | This is important, but needs to be looked at more carefully -- probably some kind of command-line tool would be best.  See [Exporting and restoring the database](./db_export_restore.md). |
| overlib | an out-of-date library. |
| old documentation | Has too many screenshots that don't apply now! |

