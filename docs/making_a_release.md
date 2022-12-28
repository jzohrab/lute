# Making a release

This is the process I currently run on my Mac.  Maybe in the future it can be a GitHub action or similar.

If any step fails, or things look bad, sort that stuff out.

* `composer test` : run all the tests.
* `composer app:release` : generate a `lute_release.zip`, and open local testing environment (pre-configured in my virtual hosts).
* In the demo environment, run through a few tutorial steps:
  * create terms
  * multi-words
  * browse
  * etc.  (This should really be automated, too much work.)
* `composer app:changelog` : generate some raw changelog notes.  Edit this and commit it.
* Create a _local_ tag (git tag `<new_tag_name>`) but **don't push it to origin**
* **RE-RUN** `composer app:release` to include the tag in the manifest.
  * Check the tag in the testing environment's "Server info" page.
* Push the tag to GitHub: `git push origin <new_tag_name>`
* Attach the lute release zip to the github release.  Maybe update the release notes in the GitHub release.
