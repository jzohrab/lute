> This is the process I currently run on my Mac.  Maybe in the future it can be a GitHub action or similar.

Releases follow the "git flow" method.  Summary of creating a release:

* create a release branch
* in a dedicated build environment (see creation notes at bottom of this page), build the release, launch it, and test it
* if all is good, tag and rebuild the release
* merge the release branch and tag, and create the GitHub release

If any step fails, or things look bad, sort that stuff out.

## 1. DEV environment: Create release branch, initial sanity checks

```
git checkout develop             # or whatever good commit
git checkout -b release_xxx
git fetch
git merge --no-ff origin/master  # To get any master hotfixes
composer test:full               # All must pass
composer dev:data:load           # For sanity checks:
```

Then run some sanity checks:

* read tutorial text
* create and save term
  * check multiple dictionaries
  * check sentence translation
  * set parent term
  * hover over term
* keyboard nav: and change statuses, 1-5, W, I, on known and unknown terms
* keyboard nav: check translation
* keyboard nav: copy a sentence, copy the paragraph
* mark rest as known
* read other tutorial text, ensure mostly known
* create a new text
* create a new language

If all good:

```
# Generate Changelog
composer app:changelog vPREVVERSION
# ... edit change log ...
git add docs/CHANGELOG.md
git commit -m "Changelog."
```

## 2. BUILD environment: build and check

```
cd ../lute_build
git fetch origin
git checkout release_xxx
composer app:release:check
# OR:
# MODE=offline composer app:release:check
```

Run through some steps again.  If anything fails, fix the release branch in the dev environment, and retry.

### 3. BUILD environment: finalize

```
git tag vNEWVERSION
composer app:release:final
git push origin vNEWVERSION
```

### 4. DEV env: finish up

```
cd ../lute_dev
git push origin vNEWVERSION   # to github
git checkout master
git merge vNEWVERSION
git push origin master
git checkout develop
git merge master
git push origin develop
```

Go to GitHub and make the release, including the generated .zip files, and the changelog notes.


# Creating the build environment

I currently create Lute releases in a separate `lute_build` folder, created as follows:

```
cd /parent/of/lute_dev
mkdir lute_build
cd lute_build
git remote add origin /path/to/lute_dev
git fetch origin
```

Branches and tags can be fetched and pushed as usual to `origin`.
