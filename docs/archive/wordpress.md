# Wordpress Integration

**Discontinued.**

The original LWT project gives each WordPress user their own set of
tables within the mysql LWT database.  For example, given users 1, 2,
and 3, there would be `1_words`, `2_words`, `3_words`.  This made the
code harder much harder to work with.

I could have changed the code to give each separate user their own
database, running on the same server, e.g. `lwt_1`, `lwt_2`, `lwt_3`,
with their own standard tables ...

However, that creates challenges when it comes to database change
management.

To support wp integration sensibly, the code needs to be modified to
have multi-user support.  In summary, something like the following:

* add a "users" table, with UserID
* in "languages", add foreign key column LgUserID
* add user stuff to tests
* add tests for multiple users
* add login screen or similar
* add security measures to ensure that users can only access resources
  that they own.  Hopefully this won't be a _huge_ problem, because it
  could pretty much all be handled by the front controller, through
  which everything is routed.

After that, everything should _pretty much_ be fine.  Anything that
joins to the Language table will be differentiated by the user ID.

Simple, right?