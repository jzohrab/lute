# Making a release

This is the process I currently run on my Mac.  Maybe in the future it can be a GitHub action or similar.

If any step fails, or things look bad, sort that stuff out.

Run all of this in the `dev` environment:

## 1. Initial sanity checks

```
# on the develop branch:
composer test:full

git push origin develop

git checkout master

# Verify up to date!
git log -n 1 master

git merge --no-ff develop

composer dev:data:load

# manually run through tutorial steps:
#  * read tutorial text
#  * create and save term
#     * check multiple dictionaries
#     * check sentence translation
#     * set parent term
#     * hover over term
#   * keyboard nav and change statuses, 1-5, W, I, on known and unknown terms
#   * keyboard nav check translation
#   * mark rest as known
#   * read other tutorial text, ensure mostly known
# * create a new text
# * create a new language

```

## 2. Prep release and verify

```
composer app:changelog vprevious_version
# edit change log

git add docs/CHANGELOG.md
git commit -m "Changelog."

# Create local provisional tag, but don't push it to origin.
git tag v.NEWVERSION

composer app:release

# Run same manual checks as above
# Check tag was set on main page
```

## 3. Release

```
git push origin master

git push origin v.NEWVERSION

# Go to GitHub and make the release, including the generated .zip files, and the changelog notes.
```
