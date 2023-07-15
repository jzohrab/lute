#!/bin/bash

# HACK HACK HACK - should use WebPack or something different,
# but this is good enough for now.
#
# Rename the js file and change refs.

set -e

echo "HACK renaming the js file and updating references."
HASH=`git log -n 1 --format="%h"`
JSFILENAME=lute.${HASH}.js
echo

echo Renaming lute.js to $JSFILENAME
mv public/js/lute.js public/js/${JSFILENAME}

# Mac OS-specific sed in place with no backup file.
echo Updating templates/base.html.twig
sed -i '' "s/lute\.js/${JSFILENAME}/" templates/base.html.twig
