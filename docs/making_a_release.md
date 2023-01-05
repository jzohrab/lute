# Making a release

This is the process I currently run on my Mac.  Maybe in the future it can be a GitHub action or similar.

If any step fails, or things look bad, sort that stuff out.

## Part 0: merge passing `develop` branch into `master`

* New code should be merged into `develop`
* Run `composer test` for `develop`
* Push `develop` to GitHub
* Wait for GitHub CI to pass
* Merge code into `master`

If `master` contained code (hotfixes) that were not in `develop` (`git log develop..master --oneline` has commits):

* Run `composer test` for `master`
* Push `master` to GitHub
* Potentially wait for GitHub CI to pass


**All following steps should be done off of `master`, unless there's a special fix release going out.**


## Part 1: catch the obvious.

This is time-consuming and should be automated!

* change .env.local to use lute_demo.  Drop the lute_demo db.
* go to home page, load the demo db
* go through some steps in the tutorial:
  * read tutorial text
  * create and save term
    * check multiple dictionaries
    * check sentence translation
    * set parent term
    * hover over term
  * keyboard nav and change statuses, 1-5, W, I, on known and unknown terms
  * keyboard nav check translation
  * mark rest as known
  * read other tutorial text, ensure mostly known
* create a new text
* create a new language

## Part 2: create test release

* `composer app:release` : generate a `lute_release.zip` and `lute_debug.zip`, and open local testing environments (pre-configured in my virtual hosts).
* In the demo environment, run through a few tutorial steps:
  * create terms
  * multi-words
  * browse
  * etc.  (This should really be automated, too much work.)

## Part 3: create actual release

* `composer app:changelog` : generate some raw changelog notes.  Edit this and commit it.
* Create a _local_ tag (git tag `<new_tag_name>`) but **don't push it to origin**
* **RE-RUN** `composer app:release` to include the tag in the manifest.
  * Check the tag in the testing environment's "Server info" page.

## Part 4: release

* Push the tag to GitHub: `git push origin <new_tag_name>`
* Attach the lute_release.zip and lute_debug.zip to the github release.  Maybe update the release notes in the GitHub release.
