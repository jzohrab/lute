#!/bin/bash

# Lute v2 can run using the built-in PHP web server.
RELTESTDIR="../lute_release"
DOCKERRELTESTDIR="../lute_release_docker"

# Open to non-existent site!
# On Chrome, the tab refreshes itself periodically, so
# when php starts, the site is shown.
open http://localhost:9999/
open http://localhost:8000/

pushd "$RELTESTDIR"
  mkdir zz_bkp
  sed -i '' 's/~\/Dropbox\/LuteBackup\//.\/zz_bkp/g' .env
  pushd public
  php -S localhost:9999 &
  popd
popd
echo "Started PHP in $RELTESTDIR ... to stop it, kill -9 <pid>"
echo

pushd "$DOCKERRELTESTDIR"
  mkdir zz_bkp
  sed -i '' 's/~\/Dropbox\/LuteBackup\//.\/zz_bkp/g' .env
  docker compose build
  docker compose up -d
popd
echo "Started docker in $DOCKERRELTESTDIR ... to stop it:"
echo "pushd $DOCKERRELTESTDIR; docker compose down; popd"
echo

# Don't bother opening the debug release.  It is the same as the other
# release except for additional dev composer dependencies.  More
# importantly, opening it up at the same time as the release can cause
# problems if both of them try to run migrations at the same time on
# the same database.
#
# open http://lute_debug.local:8080/
