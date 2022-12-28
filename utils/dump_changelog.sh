#!/bin/bash

# Dump changelog commits since a particular commit.

SINCE=$1

if [[ "$SINCE" == "" ]]; then
    echo
    echo "Please specify the starting commit or tag."
    echo
    exit 1
fi

echo "
## `date '+%Y-%m-%d'`

---

_(raw info to process)_
" > docs/tmp_CHANGELOG.tmp

git log ${SINCE}..HEAD --pretty="* %s" >> docs/tmp_CHANGELOG.tmp

echo "

_(end raw)_
---

" >> docs/tmp_CHANGELOG.tmp


# Insert the tmp_CHANGLOG.md file after the title.
# ref https://stackoverflow.com/questions/16715373/
#   insert-contents-of-a-file-after-specific-pattern-match
sed -i.bak '/^# Changelog/ r docs/tmp_CHANGELOG.tmp' docs/CHANGELOG.md

rm docs/tmp_CHANGELOG.tmp
